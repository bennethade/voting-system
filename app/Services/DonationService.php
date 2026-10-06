<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\PaymentException;
use App\Models\AwardEvent;
use App\Models\Donation;
use App\Models\User;

class DonationService
{
    public function __construct(
        private readonly ReferenceGenerator $references,
        private readonly SettingsService $settings,
    ) {}

    /**
     * @param  array{name: string, email: string, message?: ?string, anonymous?: bool}  $donor
     *
     * @throws PaymentException
     */
    public function createDonation(string $amount, array $donor, ?User $user, ?AwardEvent $event, ?string $ip): Donation
    {
        $minimum = (float) $this->settings->get('donation_min', 100);

        if ((float) $amount < $minimum) {
            throw new PaymentException('The minimum donation is '.money($minimum).'.');
        }

        return Donation::create([
            'reference' => $this->references->generate('DON', Donation::class),
            'user_id' => $user?->id,
            'award_event_id' => $event?->id,
            'donor_name' => $donor['name'],
            'email' => $donor['email'],
            'amount' => number_format((float) $amount, 2, '.', ''),
            'currency' => config('awards.currency'),
            'message' => $donor['message'] ?? null,
            'is_anonymous' => (bool) ($donor['anonymous'] ?? false),
            'status' => OrderStatus::Pending,
            'ip_address' => $ip,
        ]);
    }
}
