<?php

namespace App\Services;

use App\Exceptions\PaymentException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper over the Paystack REST API (https://paystack.com/docs/api/).
 * Only talks HTTP; deciding what a result means for an order is
 * PaymentService's job.
 */
class PaystackService
{
    public function isConfigured(): bool
    {
        return filled(config('services.paystack.secret_key'));
    }

    /**
     * POST /transaction/initialize
     *
     * @param  int  $amount  amount in the currency subunit (kobo for NGN)
     * @return array{authorization_url: string, access_code: string, reference: string}
     *
     * @throws PaymentException
     */
    public function initialize(string $email, int $amount, string $reference, string $currency, array $metadata = [], ?string $callbackUrl = null): array
    {
        $split = $this->splitParameters();

        $response = $this->send(fn (PendingRequest $http) => $http->post('/transaction/initialize', array_filter([
            'email' => $email,
            'amount' => $amount,
            'reference' => $reference,
            'currency' => $currency,
            'callback_url' => $callbackUrl ?: config('services.paystack.callback_url') ?: route('payment.callback'),
            'metadata' => $metadata ?: null,
            ...$split,
        ], fn ($v) => $v !== null && $v !== '')));

        $data = $response->json('data');

        if (! $response->successful() || ! $response->json('status') || empty($data['authorization_url'])) {
            Log::error('Paystack initialize failed', [
                'reference' => $reference,
                'http_status' => $response->status(),
                'message' => $response->json('message'),
            ]);

            throw new PaymentException('The payment could not be initialized. Please try again.');
        }

        return $data;
    }

    /**
     * Settlement routing sent with every transaction, from .env:
     * `split_code` (multi-account split) or `subaccount` (+ optional
     * `transaction_charge` in subunits and `bearer`). Empty when neither is set.
     *
     * A malformed code is refused outright rather than silently ignored, so a
     * typo can never route money differently from what was configured.
     *
     * @return array<string, string|int>
     *
     * @throws PaymentException
     */
    public function splitParameters(): array
    {
        $splitCode = trim((string) config('services.paystack.split_code'));
        $subaccount = trim((string) config('services.paystack.subaccount'));

        if ($splitCode !== '') {
            if (! preg_match('/^SPL_[A-Za-z0-9]+$/', $splitCode)) {
                $this->misconfigured('PAYSTACK_SPLIT_CODE must look like SPL_xxxxxxxx.');
            }

            if ($subaccount !== '') {
                Log::notice('Both PAYSTACK_SPLIT_CODE and PAYSTACK_SUBACCOUNT_CODE are set; using the split code.');
            }

            return ['split_code' => $splitCode];
        }

        if ($subaccount === '') {
            return [];
        }

        if (! preg_match('/^ACCT_[A-Za-z0-9]+$/', $subaccount)) {
            $this->misconfigured('PAYSTACK_SUBACCOUNT_CODE must look like ACCT_xxxxxxxx.');
        }

        $params = ['subaccount' => $subaccount];

        $charge = config('services.paystack.transaction_charge');
        if ($charge !== null && $charge !== '') {
            if (! is_numeric($charge) || (float) $charge < 0) {
                $this->misconfigured('PAYSTACK_TRANSACTION_CHARGE must be a non-negative amount in major units (e.g. 500 for ₦500).');
            }
            $params['transaction_charge'] = to_subunit($charge);
        }

        $bearer = trim((string) config('services.paystack.bearer'));
        if ($bearer !== '') {
            if (! in_array($bearer, ['account', 'subaccount'], true)) {
                $this->misconfigured('PAYSTACK_BEARER must be "account" or "subaccount".');
            }
            $params['bearer'] = $bearer;
        }

        return $params;
    }

    /** @throws PaymentException */
    private function misconfigured(string $detail): never
    {
        Log::critical('Paystack split/subaccount misconfigured: '.$detail);

        throw new PaymentException('Online payments are not available right now. Please try again later.');
    }

    /**
     * GET /transaction/verify/:reference
     *
     * Returns the transaction `data` object (status, amount, currency,
     * reference, id, channel, paid_at, gateway_response, customer...).
     *
     * @throws PaymentException when Paystack cannot be reached or rejects the request
     */
    public function verify(string $reference): array
    {
        $response = $this->send(fn (PendingRequest $http) => $http->get('/transaction/verify/'.rawurlencode($reference)));

        if (! $response->successful() || ! $response->json('status')) {
            Log::warning('Paystack verify failed', [
                'reference' => $reference,
                'http_status' => $response->status(),
                'message' => $response->json('message'),
            ]);

            throw new PaymentException('We could not verify your payment yet. Please wait while we confirm the transaction.');
        }

        return (array) $response->json('data');
    }

    /**
     * POST /refund
     *
     * @param  int|null  $amount  partial amount in subunit; null refunds in full
     *
     * @throws PaymentException
     */
    public function refund(string $transactionReference, ?int $amount = null): array
    {
        $response = $this->send(fn (PendingRequest $http) => $http->post('/refund', array_filter([
            'transaction' => $transactionReference,
            'amount' => $amount,
        ])));

        if (! $response->successful() || ! $response->json('status')) {
            Log::error('Paystack refund failed', [
                'reference' => $transactionReference,
                'http_status' => $response->status(),
                'message' => $response->json('message'),
            ]);

            throw new PaymentException($response->json('message') ?: 'The refund could not be processed.');
        }

        return (array) $response->json('data');
    }

    /**
     * Webhooks are signed with HMAC-SHA512 of the raw request body using the
     * secret key, sent in the `x-paystack-signature` header.
     */
    public function isValidSignature(string $payload, ?string $signature): bool
    {
        $secret = (string) config('services.paystack.secret_key');

        if ($secret === '' || blank($signature)) {
            return false;
        }

        return hash_equals(hash_hmac('sha512', $payload, $secret), (string) $signature);
    }

    /** @param callable(PendingRequest): Response $call */
    private function send(callable $call): Response
    {
        if (! $this->isConfigured()) {
            Log::critical('Paystack secret key is not configured.');

            throw new PaymentException('Online payments are not available right now. Please try again later.');
        }

        try {
            return $call(
                Http::baseUrl(rtrim((string) config('services.paystack.base_url'), '/'))
                    ->withToken((string) config('services.paystack.secret_key'))
                    ->acceptJson()
                    ->asJson()
                    ->timeout((int) config('services.paystack.timeout', 20))
                    ->retry(2, 300, fn ($e) => $e instanceof ConnectionException, throw: false)
            );
        } catch (ConnectionException $e) {
            Log::error('Paystack connection error', ['error' => $e->getMessage()]);

            throw new PaymentException('We could not reach the payment provider. Please try again shortly.');
        }
    }
}
