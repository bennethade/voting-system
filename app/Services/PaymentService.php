<?php

namespace App\Services;

use App\Contracts\Payable;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\PaymentSucceeded;
use App\Exceptions\PaymentException;
use App\Models\Donation;
use App\Models\EventBooking;
use App\Models\Payment;
use App\Models\User;
use App\Models\VoteOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Payment lifecycle for every payable (vote orders, donations, bookings).
 *
 * The browser redirect is never trusted. A payment only succeeds when
 * Paystack itself says so: either through a signature-verified webhook or a
 * server-to-server verify call. Both routes converge on
 * processTransaction(), which is idempotent and runs in one DB transaction
 * with the payment and payable rows locked.
 */
class PaymentService
{
    public const RESULT_PROCESSED = 'processed';

    public const RESULT_ALREADY_PROCESSED = 'already_processed';

    public const RESULT_PENDING = 'pending';

    public const RESULT_FAILED = 'failed';

    public const RESULT_AMOUNT_MISMATCH = 'amount_mismatch';

    public const RESULT_UNKNOWN_REFERENCE = 'unknown_reference';

    public const RESULT_DUPLICATE_PAYMENT = 'duplicate_payment';

    /** Paystack statuses that mean the attempt is over and did not succeed. */
    private const FAILED_STATUSES = ['failed', 'reversed'];

    public function __construct(
        private readonly PaystackService $paystack,
        private readonly VotingService $voting,
        private readonly BookingService $bookings,
        private readonly ReferenceGenerator $references,
        private readonly FraudDetectionService $fraud,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Start a payment attempt for a payable and return it with the Paystack
     * checkout URL. A payable may be retried after a failed/abandoned attempt.
     *
     * @throws PaymentException
     */
    public function initialize(Payable&Model $payable, ?User $user = null, ?string $ip = null): Payment
    {
        if (! in_array($payable->payableStatus(), [OrderStatus::Pending, OrderStatus::Failed], true)) {
            throw new PaymentException('This order has already been completed or cancelled.');
        }

        $payment = Payment::create([
            'reference' => $this->references->paymentReference(),
            'payable_type' => $payable->getMorphClass(),
            'payable_id' => $payable->getKey(),
            'user_id' => $user?->id,
            'provider' => 'paystack',
            'email' => $payable->payableEmail(),
            'amount' => $payable->payableAmount(),
            'currency' => $payable->payableCurrency(),
            'status' => PaymentStatus::Pending,
            'ip_address' => $ip,
        ]);

        $payable->forceFill(['payment_reference' => $payment->reference, 'status' => OrderStatus::Pending])->save();

        try {
            $data = $this->paystack->initialize(
                email: $payment->email,
                amount: $payment->amountInSubunit(),
                reference: $payment->reference,
                currency: $payment->currency,
                metadata: [
                    'payment_id' => $payment->id,
                    'payable_type' => $payment->payable_type,
                    'payable_reference' => $payable->getAttribute('reference'),
                    'custom_fields' => [
                        ['display_name' => 'Order', 'variable_name' => 'order', 'value' => $payable->getAttribute('reference')],
                        ['display_name' => 'Description', 'variable_name' => 'description', 'value' => $payable->payableDescription()],
                    ],
                ],
            );
        } catch (PaymentException $e) {
            $payment->forceFill(['status' => PaymentStatus::Failed, 'gateway_response' => 'Initialization failed'])->save();

            throw $e;
        }

        $payment->forceFill([
            'authorization_url' => $data['authorization_url'],
            // Which settlement routing this transaction was created with.
            'metadata' => array_filter([
                'access_code' => $data['access_code'] ?? null,
                'settlement' => $this->paystack->splitParameters() ?: null,
            ]),
        ])->save();

        return $payment;
    }

    /**
     * Ask Paystack for the authoritative state of a payment (used by the
     * callback page and the admin "re-verify" action).
     *
     * @throws PaymentException when Paystack cannot be reached
     */
    public function verify(Payment $payment, string $via = 'callback'): Payment
    {
        if ($payment->status === PaymentStatus::Success || $payment->status === PaymentStatus::Refunded) {
            return $payment;
        }

        $transaction = $this->paystack->verify($payment->reference);
        $this->processTransaction($transaction, $via);

        return $payment->refresh();
    }

    /**
     * Apply a Paystack transaction object to our records. Safe to call any
     * number of times with the same data.
     */
    public function processTransaction(array $transaction, string $via): string
    {
        $reference = (string) Arr::get($transaction, 'reference', '');

        $result = DB::transaction(function () use ($transaction, $reference, $via) {
            /** @var Payment|null $payment */
            $payment = Payment::where('reference', $reference)->lockForUpdate()->first();

            if (! $payment) {
                return self::RESULT_UNKNOWN_REFERENCE;
            }

            if (in_array($payment->status, [PaymentStatus::Success, PaymentStatus::Refunded], true)) {
                return self::RESULT_ALREADY_PROCESSED;
            }

            $status = strtolower((string) Arr::get($transaction, 'status'));

            if ($status !== 'success') {
                return $this->recordUnsuccessful($payment, $status, $transaction);
            }

            if (! $this->amountMatches($payment, $transaction)) {
                return $this->recordAmountMismatch($payment, $transaction);
            }

            $payment->forceFill([
                'status' => PaymentStatus::Success,
                'provider_transaction_id' => (string) Arr::get($transaction, 'id'),
                'channel' => Arr::get($transaction, 'channel'),
                'gateway_response' => mb_substr((string) Arr::get($transaction, 'gateway_response'), 0, 255) ?: null,
                'paid_at' => Arr::get($transaction, 'paid_at') ? now()->parse(Arr::get($transaction, 'paid_at')) : now(),
                'verified_via' => $via,
                'metadata' => array_merge(Arr::except($payment->metadata ?? [], 'amount_mismatch'), array_filter([
                    // Charged above the price = fee borne by the customer.
                    'charged_subunit' => (int) Arr::get($transaction, 'amount'),
                    'customer_fee_subunit' => max(0, (int) Arr::get($transaction, 'amount') - $payment->amountInSubunit()) ?: null,
                    'paystack_fees_subunit' => Arr::get($transaction, 'fees'),
                ], fn ($v) => $v !== null)),
            ])->save();

            $payable = $this->lockPayable($payment);

            if (! $payable) {
                Log::critical('Paid payment has no payable', ['payment' => $payment->reference]);

                return self::RESULT_UNKNOWN_REFERENCE;
            }

            if ($payable->payableStatus() === OrderStatus::Paid) {
                // A second attempt for an order that is already paid (e.g. the
                // customer paid in two tabs). Keep the money on record but do not
                // fulfil twice; staff can refund it.
                $payment->forceFill(['metadata' => array_merge($payment->metadata ?? [], ['duplicate_of_paid_order' => true])])->save();
                $this->fraud->flagDuplicatePayment($payment);

                return self::RESULT_DUPLICATE_PAYMENT;
            }

            $this->fulfil($payable, $payment);
            PaymentSucceeded::dispatch($payment);

            return self::RESULT_PROCESSED;
        });

        Log::info('Payment transaction processed', ['reference' => $reference, 'via' => $via, 'result' => $result]);

        return $result;
    }

    /**
     * Refund a successful payment through Paystack and reverse what it bought.
     *
     * @throws PaymentException
     */
    public function refund(Payment $payment, User $admin, string $reason): Payment
    {
        if ($payment->status !== PaymentStatus::Success) {
            throw new PaymentException('Only successful payments can be refunded.');
        }

        // Check everything that could block the local reversal *before* money moves.
        $payable = $payment->payable;
        if ($payable instanceof VoteOrder && $payable->awardEvent->isResultsLocked() && ! $admin->isSuperAdmin()) {
            throw new PaymentException('Results for this event are locked. Only a super administrator can refund vote orders.');
        }

        $this->paystack->refund($payment->reference);

        try {
            return $this->recordRefund($payment, $admin, $reason);
        } catch (\Throwable $e) {
            Log::critical('Refund issued at Paystack but local reversal failed', [
                'reference' => $payment->reference,
                'error' => $e->getMessage(),
            ]);

            throw new PaymentException('The refund was sent to Paystack but could not be recorded locally. Please contact technical support.');
        }
    }

    private function recordRefund(Payment $payment, User $admin, string $reason): Payment
    {
        return DB::transaction(function () use ($payment, $admin, $reason) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $payment->forceFill([
                'status' => PaymentStatus::Refunded,
                'refunded_at' => now(),
                'metadata' => array_merge($payment->metadata ?? [], ['refund_reason' => $reason, 'refunded_by' => $admin->id]),
            ])->save();

            $payable = $this->lockPayable($payment);
            $isDuplicate = (bool) ($payment->metadata['duplicate_of_paid_order'] ?? false);

            // Refunding a duplicate payment must not undo the original purchase.
            if ($payable && ! $isDuplicate) {
                $payable->forceFill(['status' => OrderStatus::Refunded])->save();

                if ($payable instanceof VoteOrder && $payable->vote) {
                    $this->voting->invalidate($payable->vote, $admin, "Order refunded: {$reason}");
                }

                if ($payable instanceof EventBooking) {
                    $this->bookings->release($payable);
                }
            }

            $this->audit->log(
                'payments.refunded',
                "Refunded payment {$payment->reference} (".money($payment->amount, $payment->currency)."): {$reason}",
                $payment,
                ['status' => PaymentStatus::Success->value],
                ['status' => PaymentStatus::Refunded->value, 'reason' => $reason],
            );

            return $payment;
        });
    }

    /** The latest payment attempt for a payable, if any. */
    public function latestPayment(Model $payable): ?Payment
    {
        return Payment::where('payable_type', $payable->getMorphClass())
            ->where('payable_id', $payable->getKey())
            ->latest('id')
            ->first();
    }

    private function fulfil(Payable&Model $payable, Payment $payment): void
    {
        $payable->forceFill([
            'status' => OrderStatus::Paid,
            'paid_at' => $payment->paid_at,
            'payment_reference' => $payment->reference,
        ])->save();

        match (true) {
            $payable instanceof VoteOrder => $this->voting->creditPaidOrder($payable),
            $payable instanceof EventBooking => $this->bookings->confirm($payable),
            $payable instanceof Donation => null, // marking it paid is all a donation needs
            default => throw new \LogicException('Unsupported payable '.get_class($payable)),
        };
    }

    private function recordUnsuccessful(Payment $payment, string $status, array $transaction): string
    {
        if (! in_array($status, self::FAILED_STATUSES, true)) {
            // abandoned / ongoing / pending: the customer may still complete it.
            return self::RESULT_PENDING;
        }

        $payment->forceFill([
            'status' => PaymentStatus::Failed,
            'gateway_response' => mb_substr((string) Arr::get($transaction, 'gateway_response', $status), 0, 255),
        ])->save();

        $payable = $this->lockPayable($payment);

        // Only mark the payable failed if this was its current attempt.
        if ($payable && $payable->payableStatus() === OrderStatus::Pending && $payable->getAttribute('payment_reference') === $payment->reference) {
            $payable->forceFill(['status' => OrderStatus::Failed])->save();
        }

        DB::afterCommit(fn () => $this->fraud->inspectFailedPayment($payment));

        return self::RESULT_FAILED;
    }

    private function recordAmountMismatch(Payment $payment, array $transaction): string
    {
        $paid = (int) Arr::get($transaction, 'amount');

        Log::critical('Payment amount mismatch', [
            'reference' => $payment->reference,
            'expected' => $payment->amountInSubunit(),
            'expected_currency' => $payment->currency,
            'received' => $paid,
            'received_currency' => Arr::get($transaction, 'currency'),
        ]);

        $payment->forceFill([
            'status' => PaymentStatus::Failed,
            'gateway_response' => 'Amount mismatch',
            'provider_transaction_id' => (string) Arr::get($transaction, 'id'),
            'metadata' => array_merge($payment->metadata ?? [], [
                'amount_mismatch' => [
                    'expected' => $payment->amountInSubunit(),
                    'received' => $paid,
                    'currency' => Arr::get($transaction, 'currency'),
                ],
            ]),
        ])->save();

        $this->fraud->flagAmountMismatch($payment, $paid);

        return self::RESULT_AMOUNT_MISMATCH;
    }

    /**
     * When the Paystack account passes transaction fees to the customer,
     * `amount` is what was charged (price + fee) and `requested_amount` is
     * the price we asked for. Either way the price we requested must match
     * our record exactly, and the customer must not have paid less than it.
     */
    private function amountMatches(Payment $payment, array $transaction): bool
    {
        $expected = $payment->amountInSubunit();
        $charged = (int) Arr::get($transaction, 'amount', -1);
        $requested = Arr::has($transaction, 'requested_amount') ? (int) Arr::get($transaction, 'requested_amount') : $charged;

        return $requested === $expected
            && $charged >= $expected
            && strtoupper((string) Arr::get($transaction, 'currency')) === strtoupper($payment->currency);
    }

    private function lockPayable(Payment $payment): (Payable&Model)|null
    {
        $class = Relation::getMorphedModel($payment->payable_type) ?? $payment->payable_type;

        return $class::query()->whereKey($payment->payable_id)->lockForUpdate()->first();
    }
}
