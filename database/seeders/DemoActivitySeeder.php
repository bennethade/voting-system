<?php

namespace Database\Seeders;

use App\Events\PaymentSucceeded;
use App\Events\VoteRecorded;
use App\Models\AwardEvent;
use App\Models\Nominee;
use App\Models\Payment;
use App\Models\User;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\VotingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Event;

/**
 * Local-only sample activity so the dashboard and leaderboard have data.
 * Votes go through the real services (free-vote rules, order creation and
 * the payment confirmation path) rather than being inserted directly.
 */
class DemoActivitySeeder extends Seeder
{
    public function run(VotingService $voting, OrderService $orders, PaymentService $payments): void
    {
        $event = AwardEvent::where('slug', 'magnified-influencer-awards-2026')->first();

        if (! $event || $event->votes()->exists()) {
            return;
        }

        // Don't send emails or queue fraud checks for fake activity.
        Event::fake([VoteRecorded::class, PaymentSucceeded::class]);

        $voters = User::factory()->count(25)->create();
        $nominees = Nominee::whereIn('category_id', $event->categories()->select('id'))->with('category.awardEvent')->get();
        $packages = $event->activePackages()->get();

        foreach ($voters as $voter) {
            foreach ($nominees->groupBy('category_id') as $categoryNominees) {
                if (random_int(0, 100) < 70) {
                    $voting->castFreeVote($voter, $categoryNominees->random(), fake()->ipv4(), 'Seeder');
                }
            }
        }

        for ($i = 0; $i < 30; $i++) {
            $voter = random_int(0, 1) ? $voters->random() : null;
            $order = $orders->createVoteOrder(
                $nominees->random(),
                $packages->random()->id,
                ['name' => $voter?->name ?? fake()->name(), 'email' => $voter?->email ?? fake()->safeEmail()],
                $voter,
                fake()->ipv4(),
                'Seeder',
            );

            $payment = Payment::create([
                'reference' => 'PAY-DEMO-'.$order->id,
                'payable_type' => $order->getMorphClass(),
                'payable_id' => $order->id,
                'user_id' => $voter?->id,
                'email' => $order->voter_email,
                'amount' => $order->amount,
                'currency' => $order->currency,
                'status' => 'pending',
            ]);
            $order->forceFill(['payment_reference' => $payment->reference])->save();

            $succeeded = random_int(0, 100) < 85;

            // Same shape as a Paystack transaction object.
            $payments->processTransaction([
                'id' => 9000000 + $order->id,
                'reference' => $payment->reference,
                'status' => $succeeded ? 'success' : 'failed',
                'amount' => $payment->amountInSubunit(),
                'currency' => $payment->currency,
                'channel' => fake()->randomElement(['card', 'bank_transfer', 'ussd']),
                'gateway_response' => $succeeded ? 'Approved' : 'Declined',
                'paid_at' => now()->subMinutes(random_int(1, 60 * 24 * 9))->toIso8601String(),
            ], 'webhook');
        }

        $this->command?->info('Demo voting activity created.');
    }
}
