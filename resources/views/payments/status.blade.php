@use('App\Enums\PaymentStatus')
@use('App\Models\Donation')
@use('App\Models\EventBooking')
@use('App\Models\VoteOrder')
@php
    $status = $payment->status;
    $isVote = $payable instanceof VoteOrder;
    $isBooking = $payable instanceof EventBooking;
    $isDonation = $payable instanceof Donation;
@endphp

<x-layouts.app :title="match ($status) { PaymentStatus::Success => 'Payment successful', PaymentStatus::Pending => 'Confirming payment', default => 'Payment not completed' }" :noindex="true">
    <section class="hero-glow text-white">
        <div class="container-page py-14 text-center sm:py-20" x-data="paymentPoller(@js($checkUrl), @js($status->value))">
            @if ($status === PaymentStatus::Success || $status === PaymentStatus::Refunded)
                <div class="mx-auto grid size-16 place-items-center rounded-full bg-emerald-400/15 text-emerald-300 ring-1 ring-emerald-300/30">
                    <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                </div>
                <h1 class="mt-6 text-4xl font-semibold sm:text-5xl">Payment successful</h1>
                <p class="mx-auto mt-3 max-w-lg text-ink-200">
                    @if ($isVote)
                        Thank you for supporting <strong class="text-white">{{ $payable->nominee->name }}</strong>. Your votes have been recorded.
                    @elseif ($isBooking)
                        Your booking for <strong class="text-white">{{ $payable->awardEvent->name }}</strong> is confirmed.
                    @else
                        Thank you for your generous donation!
                    @endif
                </p>
            @elseif ($status === PaymentStatus::Pending)
                <div class="mx-auto grid size-16 place-items-center rounded-full bg-gold-400/15 ring-1 ring-gold-300/30">
                    <svg class="size-8 animate-spin text-gold-300" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"/></svg>
                </div>
                <h1 class="mt-6 text-4xl font-semibold sm:text-5xl">Confirming your payment…</h1>
                <p class="mx-auto mt-3 max-w-lg text-ink-200">We could not verify your payment yet. Please wait while we confirm the transaction with Paystack — this page updates automatically. You'll also get an email once it's confirmed.</p>
            @else
                <div class="mx-auto grid size-16 place-items-center rounded-full bg-rose-400/15 text-rose-300 ring-1 ring-rose-300/30">
                    <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
                <h1 class="mt-6 text-4xl font-semibold sm:text-5xl">Payment not completed</h1>
                <p class="mx-auto mt-3 max-w-lg text-ink-200">Your payment was not successful{{ $payment->gateway_response ? ' ('.$payment->gateway_response.')' : '' }}. You have not been charged for this attempt.</p>
            @endif
        </div>
    </section>

    <div class="container-page -mt-8 max-w-xl">
        <div class="card overflow-hidden">
            <dl class="divide-y divide-ink-100 px-5 text-sm sm:px-6">
                @if ($isVote)
                    <div class="flex justify-between gap-4 py-3.5"><dt class="text-ink-500">Nominee</dt><dd class="text-right font-medium">{{ $payable->nominee->name }}</dd></div>
                    <div class="flex justify-between gap-4 py-3.5"><dt class="text-ink-500">Category</dt><dd class="text-right font-medium">{{ $payable->category->name }}</dd></div>
                    <div class="flex justify-between gap-4 py-3.5"><dt class="text-ink-500">Votes purchased</dt><dd class="font-display text-lg font-semibold">{{ number_format($payable->votes) }}</dd></div>
                @elseif ($isBooking)
                    <div class="flex justify-between gap-4 py-3.5"><dt class="text-ink-500">Package</dt><dd class="text-right font-medium">{{ $payable->quantity }} × {{ $payable->eventPackage->name }}</dd></div>
                    <div class="flex justify-between gap-4 py-3.5"><dt class="text-ink-500">Seats</dt><dd class="font-medium">{{ $payable->seats }}</dd></div>
                @endif
                <div class="flex justify-between gap-4 py-3.5"><dt class="text-ink-500">Amount</dt><dd class="font-display text-lg font-semibold">{{ money($payment->amount, $payment->currency) }}</dd></div>
                <div class="flex justify-between gap-4 py-3.5"><dt class="text-ink-500">Reference</dt><dd class="font-mono font-medium">{{ $payable->reference }}</dd></div>
                <div class="flex justify-between gap-4 py-3.5"><dt class="text-ink-500">Payment reference</dt><dd class="font-mono text-xs font-medium break-all">{{ $payment->reference }}</dd></div>
                <div class="flex justify-between gap-4 py-3.5"><dt class="text-ink-500">Status</dt><dd><x-status-badge :status="$status" /></dd></div>
            </dl>
            <div class="grid gap-3 border-t border-ink-100 p-5 sm:grid-cols-2 sm:p-6">
                @if ($receiptUrl)
                    <a href="{{ $receiptUrl }}" class="btn btn-ink">View receipt</a>
                @endif
                @if ($status === PaymentStatus::Failed || $status === PaymentStatus::Abandoned)
                    @if ($isVote)
                        <a href="{{ \App\Http\Controllers\VoteCheckoutController::reviewUrl($payable) }}" class="btn btn-gold">Try again</a>
                    @endif
                @endif
                @if ($isVote)
                    <a href="{{ route('nominees.show', $payable->nominee) }}" @class(['btn btn-outline', 'sm:col-span-2' => ! $receiptUrl])>Back to {{ $payable->nominee->name }}</a>
                @else
                    <a href="{{ route('home') }}" @class(['btn btn-outline', 'sm:col-span-2' => ! $receiptUrl])>Back to home</a>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
