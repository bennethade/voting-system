<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\PaymentException;
use App\Models\EventBooking;
use App\Models\EventPackage;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class BookingService
{
    public function __construct(private readonly ReferenceGenerator $references) {}

    /**
     * @param  array{name: string, email: string, phone?: ?string, organization?: ?string, notes?: ?string}  $contact
     * @param  array<int, array{name?: ?string, email?: ?string}>  $attendees
     *
     * @throws PaymentException
     */
    public function createBooking(EventPackage $package, int $quantity, array $contact, array $attendees, ?User $user, ?string $ip): EventBooking
    {
        $event = $package->awardEvent;

        if ($package->status !== 'active' || ! $event->setting('booking_enabled')) {
            throw new PaymentException('This package is not available for booking.');
        }

        if ($event->event_date && $event->event_date->isPast()) {
            throw new PaymentException('Bookings for this event have closed.');
        }

        if ($quantity < 1 || $quantity > $package->remaining()) {
            throw new PaymentException($package->remaining() > 0
                ? "Only {$package->remaining()} of this package remain."
                : 'This package is sold out.');
        }

        return EventBooking::create([
            'reference' => $this->references->generate('BKG', EventBooking::class),
            'award_event_id' => $event->id,
            'event_package_id' => $package->id,
            'user_id' => $user?->id,
            'contact_name' => $contact['name'],
            'contact_email' => $contact['email'],
            'contact_phone' => $contact['phone'] ?? null,
            'organization' => $contact['organization'] ?? null,
            'notes' => $contact['notes'] ?? null,
            'quantity' => $quantity,
            'seats' => $quantity * $package->seats,
            'unit_price' => $package->price,
            'amount' => number_format(to_subunit($package->price) * $quantity / 100, 2, '.', ''),
            'currency' => $event->currency(),
            'attendees' => array_values(array_filter($attendees, fn ($a) => filled($a['name'] ?? null))),
            'status' => OrderStatus::Pending,
            'ip_address' => $ip,
        ]);
    }

    /**
     * Called inside the payment transaction once a booking is paid. Stock was
     * checked when the booking was created, but two buyers could have raced
     * for the last table; we never reject money already taken, we flag the
     * booking for staff to resolve instead.
     */
    public function confirm(EventBooking $booking): void
    {
        $package = EventPackage::whereKey($booking->event_package_id)->lockForUpdate()->firstOrFail();

        if ($package->remaining() < $booking->quantity) {
            Log::warning('Booking oversold', ['booking' => $booking->reference, 'package' => $package->id]);
            $booking->forceFill(['metadata' => array_merge($booking->metadata ?? [], ['oversold' => true])])->save();
        }

        $package->increment('quantity_sold', $booking->quantity);
    }

    /** Return a refunded booking's quantity to stock. */
    public function release(EventBooking $booking): void
    {
        $package = EventPackage::whereKey($booking->event_package_id)->lockForUpdate()->first();

        if ($package) {
            $package->forceFill(['quantity_sold' => max(0, $package->quantity_sold - $booking->quantity)])->save();
        }
    }
}
