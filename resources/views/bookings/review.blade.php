<x-layouts.app title="Confirm your booking" :noindex="true">
    <x-page-header :eyebrow="$booking->awardEvent->name" title="Confirm your booking" subtitle="You will only be charged the amount shown." />

    <div class="container-page mt-10 max-w-xl">
        <div class="card overflow-hidden">
            <div class="bg-ink-950 p-8 text-center text-white">
                <p class="text-xs tracking-widest text-ink-300 uppercase">{{ $booking->quantity }} × {{ $booking->eventPackage->name }} · {{ $booking->seats }} seats</p>
                <p class="mt-2 font-display text-5xl font-semibold text-gold-300">{{ money($booking->amount, $booking->currency) }}</p>
            </div>
            <dl class="divide-y divide-ink-100 px-6 text-sm">
                <div class="flex justify-between py-3"><dt class="text-ink-500">Booking reference</dt><dd class="font-mono font-medium">{{ $booking->reference }}</dd></div>
                <div class="flex justify-between py-3"><dt class="text-ink-500">Unit price</dt><dd class="font-medium">{{ money($booking->unit_price, $booking->currency) }}</dd></div>
                <div class="flex justify-between gap-4 py-3"><dt class="text-ink-500">Contact</dt><dd class="text-right font-medium">{{ $booking->contact_name }}<br><span class="text-ink-500">{{ $booking->contact_email }}</span></dd></div>
            </dl>
            <div class="space-y-3 p-6">
                <form method="POST" action="{{ $payUrl }}" x-data="submitOnce" @submit="submit">
                    @csrf
                    <button class="btn btn-gold btn-lg w-full" :disabled="submitting">Confirm &amp; pay {{ money($booking->amount, $booking->currency) }}</button>
                </form>
                <a href="{{ route('bookings.create', $booking->eventPackage) }}" class="btn btn-outline w-full">Change booking</a>
            </div>
        </div>
    </div>
</x-layouts.app>
