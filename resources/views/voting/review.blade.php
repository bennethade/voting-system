<x-layouts.app title="Review your order" :noindex="true">
    <x-page-header eyebrow="Step 2 of 2" title="Confirm your votes" subtitle="Please check the details below. You will only be charged the amount shown." />

    <div class="container-page mt-10 max-w-xl">
        <div class="card overflow-hidden">
            <div class="flex items-center gap-4 border-b border-ink-100 p-5 sm:p-6">
                <div class="size-16 shrink-0 overflow-hidden rounded-2xl bg-ink-100">
                    @if ($order->nominee->image)
                        <img src="{{ media_url($order->nominee->image) }}" alt="" class="size-full object-cover">
                    @else
                        <div class="grid size-full place-items-center bg-ink-900 font-display text-xl text-gold-300">{{ initials($order->nominee->name) }}</div>
                    @endif
                </div>
                <div>
                    <p class="eyebrow">{{ $order->category->name }}</p>
                    <p class="text-xl font-semibold">{{ $order->nominee->name }}</p>
                </div>
            </div>

            <div class="bg-ink-950 p-6 text-center text-white">
                <p class="text-xs tracking-widest text-ink-300 uppercase">You are purchasing</p>
                <p class="mt-2 font-display text-5xl font-semibold">{{ number_format($order->votes) }} <span class="text-2xl">{{ \Illuminate\Support\Str::plural('vote', $order->votes) }}</span></p>
                <p class="mt-5 text-xs tracking-widest text-ink-300 uppercase">Amount</p>
                <p class="mt-1 font-display text-4xl font-semibold text-gold-300">{{ money($order->amount, $order->currency) }}</p>
            </div>

            <dl class="divide-y divide-ink-100 px-5 text-sm sm:px-6">
                <div class="flex justify-between py-3"><dt class="text-ink-500">Order reference</dt><dd class="font-mono font-medium">{{ $order->reference }}</dd></div>
                <div class="flex justify-between gap-4 py-3"><dt class="text-ink-500">Receipt email</dt><dd class="truncate font-medium">{{ $order->voter_email }}</dd></div>
                <div class="flex justify-between py-3"><dt class="text-ink-500">Event</dt><dd class="text-right font-medium">{{ $order->awardEvent->name }}</dd></div>
            </dl>

            <div class="space-y-3 p-5 sm:p-6">
                @if (! $votingOpen)
                    <x-voting-status :event="$order->awardEvent" />
                @else
                    <form method="POST" action="{{ $payUrl }}" x-data="submitOnce" @submit="submit">
                        @csrf
                        <button type="submit" class="btn btn-gold btn-lg w-full" :disabled="submitting">
                            <span x-show="!submitting">Confirm &amp; pay {{ money($order->amount, $order->currency) }}</span>
                            <span x-cloak x-show="submitting">Redirecting to Paystack…</span>
                        </button>
                    </form>
                @endif
                <a href="{{ route('nominees.show', $order->nominee) }}" class="btn btn-outline w-full">Change package</a>
                <p class="flex items-center justify-center gap-1.5 text-center text-xs text-ink-400">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                    Secure payment by Paystack. Card, bank transfer and USSD accepted.
                </p>
            </div>
        </div>
    </div>
</x-layouts.app>
