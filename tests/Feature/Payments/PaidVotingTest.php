<?php

namespace Tests\Feature\Payments;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Enums\VoteType;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReceiptController;
use App\Mail\PaymentReceiptMail;
use App\Mail\VoteConfirmationMail;
use App\Models\FraudFlag;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vote;
use App\Models\VoteOrder;
use App\Models\VotingPackage;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PaidVotingTest extends TestCase
{
    use RefreshDatabase;

    private function fakePaystackInitialize(): void
    {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'message' => 'Authorization URL created',
                'data' => ['authorization_url' => 'https://checkout.paystack.com/abc123', 'access_code' => 'abc123', 'reference' => 'x'],
            ]),
        ]);
    }

    /** Create an order, confirm it, and return [order, payment]. */
    private function startPayment(?User $user = null): array
    {
        $setup = $this->votingSetup();
        $this->fakePaystackInitialize();

        $request = $user ? $this->actingAs($user) : $this;
        $request->post(route('votes.checkout.store', $setup['nominee']), [
            'package_id' => $setup['package']->id,
            'name' => 'Guest Voter',
            'email' => 'guest@example.com',
        ]);

        $order = VoteOrder::firstOrFail();
        $payUrl = URL::temporarySignedRoute('checkout.pay', now()->addHour(), ['type' => 'vote_order', 'reference' => $order->reference]);
        $this->post($payUrl)->assertRedirect('https://checkout.paystack.com/abc123');

        return [$order->fresh(), Payment::firstOrFail(), $setup];
    }

    private function webhook(array $data, string $event = 'charge.success', ?string $signature = null)
    {
        $body = json_encode(['event' => $event, 'data' => $data]);

        return $this->call('POST', route('webhooks.paystack'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => $signature ?? $this->paystackSignature($body),
        ], $body);
    }

    private function successData(Payment $payment, array $overrides = []): array
    {
        return array_merge([
            'id' => 123456789,
            'reference' => $payment->reference,
            'status' => 'success',
            'amount' => $payment->amountInSubunit(),
            'currency' => $payment->currency,
            'channel' => 'card',
            'gateway_response' => 'Successful',
            'paid_at' => now()->toIso8601String(),
        ], $overrides);
    }

    public function test_order_uses_server_side_package_price_not_client_values(): void
    {
        ['nominee' => $nominee, 'package' => $package] = $this->votingSetup();

        $response = $this->post(route('votes.checkout.store', $nominee), [
            'package_id' => $package->id,
            'name' => 'Guest',
            'email' => 'guest@example.com',
            'amount' => 1,      // tampered
            'votes' => 99999,   // tampered
        ]);

        $order = VoteOrder::firstOrFail();
        $response->assertRedirect();
        $this->assertSame('1000.00', (string) $order->amount);
        $this->assertSame(10, $order->votes);
        $this->assertSame(OrderStatus::Pending, $order->status);
    }

    public function test_package_from_another_event_is_rejected(): void
    {
        ['nominee' => $nominee] = $this->votingSetup();
        $foreign = VotingPackage::factory()->create(); // belongs to a different event

        $this->post(route('votes.checkout.store', $nominee), [
            'package_id' => $foreign->id, 'name' => 'Guest', 'email' => 'guest@example.com',
        ])->assertSessionHas('error', 'The selected voting package is not available.');

        $this->assertSame(0, VoteOrder::count());
    }

    public function test_review_page_requires_a_valid_signature(): void
    {
        ['nominee' => $nominee, 'package' => $package] = $this->votingSetup();
        $this->post(route('votes.checkout.store', $nominee), ['package_id' => $package->id, 'name' => 'G', 'email' => 'g@example.com']);
        $order = VoteOrder::firstOrFail();

        $this->get(route('votes.checkout.review', $order))->assertForbidden();
        $this->post(route('checkout.pay', ['type' => 'vote_order', 'reference' => $order->reference]))->assertForbidden();
    }

    public function test_payment_initialization_sends_server_amount_in_kobo(): void
    {
        [$order, $payment] = $this->startPayment();

        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertSame($payment->reference, $order->payment_reference);

        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/transaction/initialize')
            && $r['amount'] === 100000
            && $r['email'] === 'guest@example.com'
            && $r['reference'] === $payment->reference
            && $r->hasHeader('Authorization', 'Bearer '.self::PAYSTACK_SECRET));
    }

    public function test_initialization_failure_shows_friendly_error(): void
    {
        $setup = $this->votingSetup();
        Http::fake(['api.paystack.co/*' => Http::response(['status' => false, 'message' => 'Invalid key'], 401)]);
        $this->post(route('votes.checkout.store', $setup['nominee']), ['package_id' => $setup['package']->id, 'name' => 'G', 'email' => 'g@example.com']);
        $order = VoteOrder::firstOrFail();

        $this->from('/review')->post(URL::temporarySignedRoute('checkout.pay', now()->addHour(), ['type' => 'vote_order', 'reference' => $order->reference]))
            ->assertRedirect('/review')
            ->assertSessionHas('error', 'The payment could not be initialized. Please try again.');

        $this->assertSame(PaymentStatus::Failed, Payment::first()->status);
    }

    public function test_cannot_pay_once_voting_has_closed(): void
    {
        $setup = $this->votingSetup();
        $this->post(route('votes.checkout.store', $setup['nominee']), ['package_id' => $setup['package']->id, 'name' => 'G', 'email' => 'g@example.com']);
        $order = VoteOrder::firstOrFail();
        $setup['event']->update(['voting_end_at' => now()->subMinute()]);

        $this->post(URL::temporarySignedRoute('checkout.pay', now()->addHour(), ['type' => 'vote_order', 'reference' => $order->reference]))
            ->assertSessionHas('error', 'The voting period has ended.');

        $this->assertSame(0, Payment::count());
    }

    public function test_successful_webhook_credits_votes_once_and_sends_emails(): void
    {
        Mail::fake();
        [$order, $payment, $setup] = $this->startPayment();

        $this->webhook($this->successData($payment))->assertOk();

        $order->refresh();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertSame(PaymentStatus::Success, $payment->fresh()->status);
        $this->assertSame('webhook', $payment->fresh()->verified_via);

        $vote = Vote::where('vote_order_id', $order->id)->firstOrFail();
        $this->assertSame(VoteType::Paid, $vote->vote_type);
        $this->assertSame(10, $vote->votes);
        $this->assertSame(10, $setup['nominee']->fresh()->total_votes);

        Mail::assertSent(PaymentReceiptMail::class, fn ($m) => $m->hasTo('guest@example.com'));
        Mail::assertSent(VoteConfirmationMail::class);
    }

    public function test_duplicate_webhooks_do_not_duplicate_votes(): void
    {
        [$order, $payment, $setup] = $this->startPayment();
        $data = $this->successData($payment);

        $this->webhook($data)->assertOk();
        $this->webhook($data)->assertOk();
        $this->webhook($data)->assertOk();

        $this->assertSame(1, Vote::where('vote_order_id', $order->id)->count());
        $this->assertSame(10, $setup['nominee']->fresh()->total_votes);
        $this->assertDatabaseHas('payment_webhook_logs', ['reference' => $payment->reference, 'result' => 'already_processed']);
    }

    public function test_webhook_with_invalid_signature_is_rejected(): void
    {
        [$order, $payment] = $this->startPayment();

        $this->webhook($this->successData($payment), signature: 'forged')->assertStatus(401);

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        $this->assertSame(0, Vote::count());
        $this->assertDatabaseHas('payment_webhook_logs', ['signature_valid' => false, 'result' => 'invalid_signature']);
    }

    public function test_amount_mismatch_does_not_credit_votes_and_raises_flag(): void
    {
        [$order, $payment] = $this->startPayment();

        $this->webhook($this->successData($payment, ['amount' => 100]))->assertOk();

        $this->assertSame(0, Vote::count());
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        $this->assertSame(PaymentStatus::Failed, $payment->fresh()->status);
        $this->assertSame(1, FraudFlag::where('signature', "amount_mismatch:{$payment->id}")->count());
    }

    public function test_customer_borne_paystack_fee_is_accepted(): void
    {
        [$order, $payment, $setup] = $this->startPayment();

        // ₦1,000 order with the 1.5% fee passed to the customer.
        $this->webhook($this->successData($payment, [
            'amount' => 101523,
            'requested_amount' => 100000,
            'fees' => 1523,
        ]))->assertOk();

        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        $this->assertSame(10, $setup['nominee']->fresh()->total_votes);
        $this->assertSame(1523, $payment->fresh()->metadata['customer_fee_subunit']);
    }

    public function test_requested_amount_must_match_order_even_with_fees(): void
    {
        [$order, $payment] = $this->startPayment();

        $this->webhook($this->successData($payment, ['amount' => 1523, 'requested_amount' => 100]))->assertOk();
        $this->webhook($this->successData($payment, ['amount' => 50000, 'requested_amount' => 100000]))->assertOk();

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        $this->assertSame(0, Vote::count());
    }

    public function test_failed_mismatch_can_be_reverified_after_correct_data(): void
    {
        [$order, $payment] = $this->startPayment();
        $service = app(PaymentService::class);

        $service->processTransaction($this->successData($payment, ['amount' => 101523]), 'callback'); // old behaviour: no requested_amount
        $this->assertSame(PaymentStatus::Failed, $payment->fresh()->status);

        $service->processTransaction($this->successData($payment, ['amount' => 101523, 'requested_amount' => 100000]), 'manual');
        $this->assertSame(PaymentStatus::Success, $payment->fresh()->status);
        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
    }

    public function test_currency_mismatch_does_not_credit_votes(): void
    {
        [, $payment] = $this->startPayment();

        $this->webhook($this->successData($payment, ['currency' => 'USD']))->assertOk();

        $this->assertSame(0, Vote::count());
    }

    public function test_failed_transaction_marks_order_failed(): void
    {
        [$order, $payment] = $this->startPayment();

        app(PaymentService::class)->processTransaction($this->successData($payment, ['status' => 'failed', 'gateway_response' => 'Declined']), 'callback');

        $this->assertSame(OrderStatus::Failed, $order->fresh()->status);
        $this->assertSame(PaymentStatus::Failed, $payment->fresh()->status);
        $this->assertSame(0, Vote::count());
    }

    public function test_abandoned_transaction_stays_pending(): void
    {
        [$order, $payment] = $this->startPayment();

        $result = app(PaymentService::class)->processTransaction($this->successData($payment, ['status' => 'abandoned']), 'callback');

        $this->assertSame('pending', $result);
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_unknown_reference_is_acknowledged_without_side_effects(): void
    {
        $this->webhook(['reference' => 'PAY-UNKNOWN', 'status' => 'success', 'amount' => 100, 'currency' => 'NGN'])->assertOk();

        $this->assertDatabaseHas('payment_webhook_logs', ['reference' => 'PAY-UNKNOWN', 'result' => 'unknown_reference']);
    }

    public function test_callback_verifies_with_paystack_before_crediting(): void
    {
        [$order, $payment] = $this->startPayment();

        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => $this->successData($payment)]),
        ]);

        $this->get(route('payment.callback', ['reference' => $payment->reference]))->assertRedirect();

        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), '/transaction/verify/'.$payment->reference));
        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        $this->assertSame('callback', $payment->fresh()->verified_via);
    }

    public function test_callback_query_parameters_alone_never_mark_order_paid(): void
    {
        [$order, $payment] = $this->startPayment();

        // Paystack says the transaction is not successful, whatever the URL claims.
        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => $this->successData($payment, ['status' => 'abandoned'])]),
        ]);

        $this->get(route('payment.callback', ['reference' => $payment->reference, 'status' => 'success']));

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        $this->assertSame(0, Vote::count());
    }

    public function test_status_page_shows_pending_until_confirmed(): void
    {
        [, $payment] = $this->startPayment();

        $this->get(PaymentController::statusUrl($payment))
            ->assertOk()
            ->assertSee('Confirming your payment')
            ->assertDontSee('Payment successful');
    }

    public function test_refund_reverses_votes(): void
    {
        [$order, $payment, $setup] = $this->startPayment();
        $this->webhook($this->successData($payment));

        Http::fake(['api.paystack.co/refund' => Http::response(['status' => true, 'data' => ['status' => 'pending']])]);
        $admin = User::factory()->role(Role::Admin)->create();

        $this->actingAs($admin)->post(route('admin.payments.refund', $payment), [
            'reason' => 'Customer request',
            'confirm_reference' => $payment->reference,
        ])->assertSessionHas('success');

        $this->assertSame(PaymentStatus::Refunded, $payment->fresh()->status);
        $this->assertSame(OrderStatus::Refunded, $order->fresh()->status);
        $this->assertSame(0, $setup['nominee']->fresh()->total_votes);
        $this->assertSame(Vote::STATUS_INVALIDATED, Vote::where('vote_order_id', $order->id)->value('status'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'payments.refunded']);
    }

    public function test_logged_in_voter_order_is_linked_to_account(): void
    {
        $user = User::factory()->create();
        [$order, $payment] = $this->startPayment($user);

        $this->assertSame($user->id, $order->user_id);
        $this->assertSame($user->email, $order->voter_email);

        $this->webhook($this->successData($payment));
        $this->actingAs($user)->get(route('receipts.vote', $order))->assertOk()->assertSee($order->reference);
    }

    public function test_receipt_is_private(): void
    {
        [$order, $payment] = $this->startPayment();
        $this->webhook($this->successData($payment));

        $this->actingAs(User::factory()->create())->get(route('receipts.vote', $order))->assertForbidden();
        $this->get(ReceiptController::voteUrl($order))->assertOk();
    }
}
