@php
    $payable = $payment->payable;
    $payableLink = match ($payment->payable_type) {
        'vote_order' => auth()->user()->can('orders.view') && $payable ? route('admin.orders.show', $payable) : null,
        'donation' => auth()->user()->can('donations.view') && $payable ? route('admin.donations.show', $payable) : null,
        'event_booking' => auth()->user()->can('bookings.view') && $payable ? route('admin.bookings.show', $payable) : null,
        default => null,
    };
@endphp

<x-layouts.admin :title="'Payment '.$payment->reference">
    <x-admin.page-header :title="'Payment '.$payment->reference">
        <x-slot:actions>
            @if ($payment->status === \App\Enums\PaymentStatus::Pending || $payment->status === \App\Enums\PaymentStatus::Failed)
                <form method="POST" action="{{ route('admin.payments.verify', $payment) }}">
                    @csrf
                    <button class="btn btn-outline">Re-verify with Paystack</button>
                </form>
            @endif
            <a href="{{ route('admin.payments.index') }}" class="btn btn-outline">Back</a>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($payment->metadata['amount_mismatch'] ?? false)
        <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
            <strong>Amount mismatch.</strong> Expected {{ number_format($payment->metadata['amount_mismatch']['expected']) }} subunits but Paystack reported {{ number_format($payment->metadata['amount_mismatch']['received']) }} ({{ $payment->metadata['amount_mismatch']['currency'] }}). Nothing was credited.
        </div>
    @endif
    @if ($payment->metadata['duplicate_of_paid_order'] ?? false)
        <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            <strong>Duplicate payment.</strong> This payment succeeded for an order that was already paid by another attempt. Nothing extra was credited; consider refunding it.
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card xl:col-span-2">
            <dl class="grid text-sm sm:grid-cols-2">
                @foreach ([
                    'Status' => null,
                    'Amount' => money($payment->amount, $payment->currency).' ('.number_format($payment->amountInSubunit()).' subunits)',
                    'For' => ($types[$payment->payable_type] ?? $payment->payable_type).' '.($payable?->reference ?? ''),
                    'Email' => $payment->email,
                    'Channel' => $payment->channel ?? '—',
                    'Gateway response' => $payment->gateway_response ?? '—',
                    'Verified via' => $payment->verified_via ?? '—',
                    'Paystack transaction ID' => $payment->provider_transaction_id ?? '—',
                    'Created' => $payment->created_at->format('j M Y, g:i:sa'),
                    'Paid at' => $payment->paid_at?->format('j M Y, g:i:sa') ?? '—',
                    'Refunded at' => $payment->refunded_at?->format('j M Y, g:i:sa') ?? '—',
                    'IP address' => $payment->ip_address ?? '—',
                ] as $label => $value)
                    <div class="border-b border-ink-100 px-5 py-3.5">
                        <dt class="text-xs text-ink-500">{{ $label }}</dt>
                        <dd class="mt-0.5 font-medium break-words text-ink-900">
                            @if ($label === 'Status')
                                <x-status-badge :status="$payment->status" />
                            @elseif ($label === 'For' && $payableLink)
                                <a href="{{ $payableLink }}" class="hover:text-gold-700">{{ $value }}</a>
                            @else
                                {{ $value }}
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <div class="space-y-6">
            @can('orders.refund')
                @if ($payment->status === \App\Enums\PaymentStatus::Success)
                    <form method="POST" action="{{ route('admin.payments.refund', $payment) }}" class="card space-y-4 border-rose-200 p-5" onsubmit="return confirm('Refund this payment through Paystack? This cannot be undone.')">
                        @csrf
                        <h3 class="font-sans text-base font-semibold text-rose-800">Refund payment</h3>
                        <p class="text-xs text-ink-500">Issues a full refund through Paystack. For vote orders the credited votes are invalidated; for bookings the seats are released.</p>
                        <x-textarea name="reason" label="Reason" rows="3" required />
                        <x-input name="confirm_reference" label="Type the payment reference to confirm" :hint="$payment->reference" required autocomplete="off" />
                        <button class="btn btn-danger w-full">Refund {{ money($payment->amount, $payment->currency) }}</button>
                    </form>
                @endif
            @endcan

            <section class="card overflow-hidden">
                <h3 class="border-b border-ink-100 px-5 py-4 font-sans text-base font-semibold">Webhook deliveries</h3>
                @forelse ($webhooks as $hook)
                    <div class="border-b border-ink-50 px-5 py-3 text-xs last:border-0">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-mono font-medium">{{ $hook->event ?? 'unknown' }}</span>
                            <span @class(['badge', 'bg-emerald-50 text-emerald-700' => $hook->signature_valid, 'bg-rose-50 text-rose-700' => ! $hook->signature_valid])>{{ $hook->signature_valid ? 'Signed' : 'Bad signature' }}</span>
                        </div>
                        <p class="mt-1 text-ink-500">{{ $hook->created_at->format('j M Y, g:i:sa') }} · result: <strong>{{ $hook->result ?? '—' }}</strong></p>
                    </div>
                @empty
                    <p class="px-5 py-4 text-sm text-ink-400">No webhooks received for this payment.</p>
                @endforelse
            </section>
        </div>
    </div>
</x-layouts.admin>
