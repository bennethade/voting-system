<x-layouts.receipt title="Booking receipt">
    <dl class="divide-y divide-ink-100 px-6 text-sm">
        <div class="flex justify-between gap-4 py-3.5"><dt class="text-ink-500">Event</dt><dd class="text-right font-semibold">{{ $booking->awardEvent->name }}</dd></div>
        @if ($booking->awardEvent->event_date)
            <div class="flex justify-between gap-4 py-3.5"><dt class="text-ink-500">Date</dt><dd class="text-right font-medium">{{ $booking->awardEvent->event_date->format('l, j F Y \a\t g:ia') }}</dd></div>
        @endif
        @if ($booking->awardEvent->venue)
            <div class="flex justify-between gap-4 py-3.5"><dt class="text-ink-500">Venue</dt><dd class="text-right font-medium">{{ $booking->awardEvent->venue }}</dd></div>
        @endif
        <div class="flex justify-between gap-4 py-3.5"><dt class="text-ink-500">Package</dt><dd class="text-right font-medium">{{ $booking->quantity }} × {{ $booking->eventPackage->name }}</dd></div>
        <div class="flex justify-between gap-4 py-3.5"><dt class="text-ink-500">Seats</dt><dd class="font-display text-xl font-semibold">{{ $booking->seats }}</dd></div>
        <div class="flex justify-between gap-4 py-3.5"><dt class="text-ink-500">Amount paid</dt><dd class="font-display text-xl font-semibold">{{ money($booking->amount, $booking->currency) }}</dd></div>
        <div class="flex justify-between gap-4 py-3.5"><dt class="text-ink-500">Booking reference</dt><dd class="font-mono font-medium">{{ $booking->reference }}</dd></div>
        <div class="flex justify-between gap-4 py-3.5"><dt class="text-ink-500">Booked by</dt><dd class="text-right font-medium">{{ $booking->contact_name }}<br><span class="text-ink-500">{{ $booking->contact_email }}</span></dd></div>
        @if ($booking->attendees)
            <div class="py-3.5"><dt class="text-ink-500">Attendees</dt><dd class="mt-2 grid gap-1 font-medium">@foreach ($booking->attendees as $a)<span>{{ $a['name'] }}@if (! empty($a['email'])) <span class="text-ink-400">· {{ $a['email'] }}</span>@endif</span>@endforeach</dd></div>
        @endif
        <div class="flex justify-between gap-4 py-3.5"><dt class="text-ink-500">Paid on</dt><dd class="font-medium">{{ $booking->paid_at?->format('d F Y, g:ia') }}</dd></div>
    </dl>
</x-layouts.receipt>
