<?php

namespace Tests\Feature\Payments;

use App\Enums\PaymentStatus;
use App\Models\Donation;
use App\Models\EventBooking;
use App\Models\EventPackage;
use App\Models\Payment;
use App\Models\VoteOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SplitPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => ['authorization_url' => 'https://checkout.paystack.com/x', 'access_code' => 'x', 'reference' => 'x'],
            ]),
        ]);
    }

    private function payVoteOrder()
    {
        $setup = $this->votingSetup();
        $this->post(route('votes.checkout.store', $setup['nominee']), ['package_id' => $setup['package']->id, 'name' => 'G', 'email' => 'g@example.com']);

        return $this->post(URL::temporarySignedRoute('checkout.pay', now()->addHour(), ['type' => 'vote_order', 'reference' => VoteOrder::first()->reference]));
    }

    private function sentInitialize(): ?HttpRequest
    {
        return Http::recorded(fn (HttpRequest $r) => str_ends_with($r->url(), '/transaction/initialize'))->first()[0] ?? null;
    }

    public function test_split_code_is_sent_with_vote_payments(): void
    {
        config(['services.paystack.split_code' => 'SPL_abc123']);

        $this->payVoteOrder()->assertRedirect('https://checkout.paystack.com/x');

        $request = $this->sentInitialize();
        $this->assertSame('SPL_abc123', $request['split_code']);
        $this->assertArrayNotHasKey('subaccount', $request->data());
        $this->assertSame(['split_code' => 'SPL_abc123'], Payment::first()->metadata['settlement']);
    }

    public function test_subaccount_charge_and_bearer_are_sent_with_donations(): void
    {
        config([
            'services.paystack.subaccount' => 'ACCT_xyz789',
            'services.paystack.transaction_charge' => '250',
            'services.paystack.bearer' => 'subaccount',
        ]);

        $this->post(route('donate.store'), ['amount' => 5000, 'name' => 'Donor', 'email' => 'd@example.com']);
        $this->post(URL::temporarySignedRoute('checkout.pay', now()->addHour(), ['type' => 'donation', 'reference' => Donation::first()->reference]))
            ->assertRedirect('https://checkout.paystack.com/x');

        $request = $this->sentInitialize();
        $this->assertSame('ACCT_xyz789', $request['subaccount']);
        $this->assertSame(25000, $request['transaction_charge']); // ₦250 in kobo
        $this->assertSame('subaccount', $request['bearer']);
        $this->assertArrayNotHasKey('split_code', $request->data());
    }

    public function test_routing_also_applies_to_bookings(): void
    {
        config(['services.paystack.subaccount' => 'ACCT_xyz789']);
        ['event' => $event] = $this->votingSetup();
        $package = new EventPackage(['award_event_id' => $event->id, 'name' => 'VIP', 'price' => 10000, 'seats' => 10, 'quantity_available' => 5, 'status' => 'active']);
        $package->save();

        $this->post(route('bookings.store', $package), ['quantity' => 1, 'name' => 'B', 'email' => 'b@example.com', 'phone' => '08012345678']);
        $this->post(URL::temporarySignedRoute('checkout.pay', now()->addHour(), ['type' => 'event_booking', 'reference' => EventBooking::first()->reference]));

        $this->assertSame('ACCT_xyz789', $this->sentInitialize()['subaccount']);
    }

    public function test_split_code_takes_precedence_over_subaccount(): void
    {
        config(['services.paystack.split_code' => 'SPL_abc123', 'services.paystack.subaccount' => 'ACCT_xyz789']);

        $this->payVoteOrder();

        $request = $this->sentInitialize();
        $this->assertSame('SPL_abc123', $request['split_code']);
        $this->assertArrayNotHasKey('subaccount', $request->data());
    }

    public function test_no_routing_parameters_when_unset(): void
    {
        $this->payVoteOrder();

        $data = $this->sentInitialize()->data();
        foreach (['split_code', 'subaccount', 'transaction_charge', 'bearer'] as $key) {
            $this->assertArrayNotHasKey($key, $data);
        }
    }

    public function test_malformed_code_blocks_payment_instead_of_misrouting(): void
    {
        config(['services.paystack.subaccount' => 'not-a-code']);

        $this->payVoteOrder()->assertSessionHas('error');

        Http::assertNothingSent();
        $this->assertSame(PaymentStatus::Failed, Payment::first()->status);
    }

    public function test_invalid_bearer_blocks_payment(): void
    {
        config(['services.paystack.subaccount' => 'ACCT_xyz789', 'services.paystack.bearer' => 'everyone']);

        $this->payVoteOrder()->assertSessionHas('error');

        Http::assertNothingSent();
    }
}
