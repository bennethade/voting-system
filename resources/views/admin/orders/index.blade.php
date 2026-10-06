<x-layouts.admin title="Vote orders">
    <x-admin.page-header title="Vote orders" subtitle="Paid-vote purchases. Votes are credited only after Paystack confirms the payment.">
        <x-slot:actions>
            <a href="{{ route('admin.orders.export', request()->query()) }}" class="btn btn-ink">Export CSV</a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.filters :dates="true" placeholder="Reference, payment ref, email, name…">
        <x-select name="event" label="Event" :options="$events->pluck('name', 'id')" :value="request('event')" placeholder="All events" class="[&_label]:text-xs" />
        <x-select name="status" label="Status" :options="collect(\App\Enums\OrderStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])" :value="request('status')" placeholder="All statuses" class="[&_label]:text-xs" />
    </x-admin.filters>

    <div class="mb-6 grid grid-cols-3 gap-3 sm:gap-4">
        <x-admin.stat label="Orders" :value="number_format($totals->orders)" />
        <x-admin.stat label="Paid revenue" :value="money($totals->revenue)" />
        <x-admin.stat label="Votes sold" :value="number_format($totals->votes)" />
    </div>

    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Nominee</th>
                        <th class="text-right"><x-admin.sort-link column="votes" label="Votes" /></th>
                        <th class="text-right"><x-admin.sort-link column="amount" label="Amount" /></th>
                        <th>Voter</th>
                        <th>Status</th>
                        <th><x-admin.sort-link column="created_at" label="Created" /></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td><a href="{{ route('admin.orders.show', $order) }}" class="font-mono text-xs font-medium text-ink-900 hover:text-gold-700">{{ $order->reference }}</a></td>
                            <td><span class="font-medium text-ink-900">{{ $order->nominee->name }}</span><span class="block text-xs text-ink-400">{{ $order->category->name }} · {{ $order->awardEvent->name }}</span></td>
                            <td class="text-right tabular-nums">{{ number_format($order->votes) }}</td>
                            <td class="text-right font-semibold tabular-nums">{{ money($order->amount, $order->currency) }}</td>
                            <td class="text-xs">{{ $order->voter_name }}<br><span class="text-ink-400">{{ $order->voter_email }}</span></td>
                            <td><x-status-badge :status="$order->status" /></td>
                            <td class="text-xs whitespace-nowrap">{{ $order->created_at->format('j M Y, g:ia') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty-state title="No orders match these filters" class="border-0" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $orders->links() }}</div>
</x-layouts.admin>
