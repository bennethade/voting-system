<x-layouts.voter title="Orders">
    @if ($orders->isEmpty())
        <x-empty-state title="No orders yet" message="Vote packages you buy will appear here." />
    @else
        <div class="card overflow-hidden">
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Reference</th><th>Nominee</th><th class="text-right">Votes</th><th class="text-right">Amount</th><th>Status</th><th>Date</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td class="font-mono text-xs">{{ $order->reference }}</td>
                                <td class="font-medium text-ink-900">{{ $order->nominee->name }}<span class="block text-xs font-normal text-ink-400">{{ $order->category->name }}</span></td>
                                <td class="text-right tabular-nums">{{ number_format($order->votes) }}</td>
                                <td class="text-right font-semibold tabular-nums">{{ money($order->amount, $order->currency) }}</td>
                                <td><x-status-badge :status="$order->status" /></td>
                                <td class="whitespace-nowrap">{{ $order->created_at->format('j M Y') }}</td>
                                <td class="text-right whitespace-nowrap">
                                    @if ($order->isPaid())
                                        <a href="{{ route('receipts.vote', $order) }}" class="text-sm font-medium text-gold-700">Receipt</a>
                                    @elseif ($order->status === \App\Enums\OrderStatus::Pending || $order->status === \App\Enums\OrderStatus::Failed)
                                        <a href="{{ \App\Http\Controllers\VoteCheckoutController::reviewUrl($order) }}" class="text-sm font-medium text-gold-700">Complete payment</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-6">{{ $orders->links() }}</div>
    @endif
</x-layouts.voter>
