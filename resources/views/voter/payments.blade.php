<x-layouts.voter title="Payments">
    @if ($payments->isEmpty())
        <x-empty-state title="No payments yet" />
    @else
        <div class="card overflow-hidden">
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Payment reference</th><th>For</th><th class="text-right">Amount</th><th>Channel</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr>
                                <td class="font-mono text-xs">{{ $payment->reference }}</td>
                                <td>
                                    @switch($payment->payable_type)
                                        @case('vote_order') Vote order @break
                                        @case('donation') Donation @break
                                        @case('event_booking') Table booking @break
                                    @endswitch
                                    <span class="block font-mono text-xs text-ink-400">{{ $payment->payable?->reference }}</span>
                                </td>
                                <td class="text-right font-semibold tabular-nums">{{ money($payment->amount, $payment->currency) }}</td>
                                <td>{{ $payment->channel ? ucfirst(str_replace('_', ' ', $payment->channel)) : '—' }}</td>
                                <td><x-status-badge :status="$payment->status" /></td>
                                <td class="whitespace-nowrap">{{ $payment->created_at->format('j M Y, g:ia') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-6">{{ $payments->links() }}</div>
    @endif
</x-layouts.voter>
