<x-layouts.app title="Confirm your donation" :noindex="true">
    <x-page-header title="Confirm your donation" subtitle="You will only be charged the amount shown." />

    <div class="container-page mt-10 max-w-xl">
        <div class="card overflow-hidden">
            <div class="bg-ink-950 p-8 text-center text-white">
                <p class="text-xs tracking-widest text-ink-300 uppercase">Donation amount</p>
                <p class="mt-2 font-display text-5xl font-semibold text-gold-300">{{ money($donation->amount, $donation->currency) }}</p>
            </div>
            <dl class="divide-y divide-ink-100 px-6 text-sm">
                <div class="flex justify-between py-3"><dt class="text-ink-500">Reference</dt><dd class="font-mono font-medium">{{ $donation->reference }}</dd></div>
                <div class="flex justify-between py-3"><dt class="text-ink-500">Name</dt><dd class="font-medium">{{ $donation->donor_name }}{{ $donation->is_anonymous ? ' (anonymous)' : '' }}</dd></div>
                <div class="flex justify-between gap-4 py-3"><dt class="text-ink-500">Email</dt><dd class="truncate font-medium">{{ $donation->email }}</dd></div>
            </dl>
            <div class="space-y-3 p-6">
                <form method="POST" action="{{ $payUrl }}" x-data="submitOnce" @submit="submit">
                    @csrf
                    <button class="btn btn-gold btn-lg w-full" :disabled="submitting">Confirm &amp; pay {{ money($donation->amount, $donation->currency) }}</button>
                </form>
                <a href="{{ route('donate') }}" class="btn btn-outline w-full">Change amount</a>
            </div>
        </div>
    </div>
</x-layouts.app>
