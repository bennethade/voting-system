<x-layouts.admin :title="'Order '.$order->reference">
    <x-admin.page-header :title="'Order '.$order->reference">
        <x-slot:actions>
            @if ($order->isPaid())
                <a href="{{ route('receipts.vote', $order) }}" target="_blank" class="btn btn-outline">Receipt</a>
            @endif
            <a href="{{ route('admin.orders.index') }}" class="btn btn-outline">Back</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card xl:col-span-2">
            <dl class="grid divide-y divide-ink-100 text-sm sm:grid-cols-2 sm:divide-y-0">
                @foreach ([
                    'Status' => null,
                    'Nominee' => $order->nominee->name,
                    'Category' => $order->category->name,
                    'Event' => $order->awardEvent->name,
                    'Package' => ($order->metadata['package_name'] ?? $order->votingPackage?->name ?? '—'),
                    'Votes' => number_format($order->votes),
                    'Amount' => money($order->amount, $order->currency),
                    'Voter' => $order->voter_name.' · '.$order->voter_email.($order->voter_phone ? ' · '.$order->voter_phone : ''),
                    'Account' => $order->user ? $order->user->name.' (#'.$order->user->id.')' : 'Guest checkout',
                    'Payment reference' => $order->payment_reference ?? '—',
                    'Created' => $order->created_at->format('j M Y, g:i:sa'),
                    'Paid at' => $order->paid_at?->format('j M Y, g:i:sa') ?? '—',
                    'IP address' => $order->ip_address ?? '—',
                    'User agent' => \Illuminate\Support\Str::limit((string) $order->user_agent, 80) ?: '—',
                ] as $label => $value)
                    <div class="px-5 py-3.5 sm:border-b sm:border-ink-100">
                        <dt class="text-xs text-ink-500">{{ $label }}</dt>
                        <dd class="mt-0.5 font-medium text-ink-900 break-words">@if ($label === 'Status')<x-status-badge :status="$order->status" />@else{{ $value }}@endif</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section class="card p-5">
            <h3 class="font-sans text-base font-semibold">Ledger entry</h3>
            @if ($order->vote)
                <p class="mt-3 text-sm">Vote entry <strong>#{{ $order->vote->id }}</strong> credited <strong>{{ number_format($order->vote->votes) }}</strong> votes on {{ $order->vote->created_at->format('j M Y, g:ia') }}.</p>
                <p class="mt-2"><x-status-badge :status="$order->vote->status" /></p>
                @if (! $order->vote->isValid())
                    <p class="mt-2 text-xs text-ink-500">Invalidated {{ $order->vote->invalidated_at?->format('j M Y, g:ia') }} by {{ $order->vote->invalidator?->name }} — {{ $order->vote->metadata['invalidation_reason'] ?? '' }}</p>
                @endif
                @can('votes.view')
                    <a href="{{ route('admin.votes.index', ['q' => $order->reference]) }}" class="btn btn-outline btn-sm mt-4">View in ledger</a>
                @endcan
            @else
                <p class="mt-3 text-sm text-ink-500">No votes credited — the order has not been paid.</p>
            @endif
        </section>
    </div>

    <section class="card mt-6 overflow-hidden">
        <h3 class="border-b border-ink-100 px-5 py-4 font-sans text-base font-semibold">Payment attempts</h3>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Payment reference</th><th class="text-right">Amount</th><th>Status</th><th>Channel</th><th>Verified via</th><th>Created</th></tr></thead>
                <tbody>
                    @forelse ($order->payments as $payment)
                        <tr>
                            <td>
                                @can('payments.view')<a href="{{ route('admin.payments.show', $payment) }}" class="font-mono text-xs text-ink-900 hover:text-gold-700">{{ $payment->reference }}</a>@else<span class="font-mono text-xs">{{ $payment->reference }}</span>@endcan
                            </td>
                            <td class="text-right tabular-nums">{{ money($payment->amount, $payment->currency) }}</td>
                            <td><x-status-badge :status="$payment->status" /></td>
                            <td>{{ $payment->channel ?? '—' }}</td>
                            <td>{{ $payment->verified_via ?? '—' }}</td>
                            <td class="text-xs whitespace-nowrap">{{ $payment->created_at->format('j M Y, g:ia') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-6 text-center text-ink-400">No payment attempts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.admin>
