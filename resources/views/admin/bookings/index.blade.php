<x-layouts.admin title="Event bookings">
    <x-admin.page-header title="Event bookings" subtitle="Table and seat bookings for awards nights.">
        <x-slot:actions>
            <a href="{{ route('admin.bookings.export', request()->query()) }}" class="btn btn-outline">Export CSV</a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.filters placeholder="Reference, contact, organization…" :dates="true">
        <x-select name="status" label="Status" :options="collect(\App\Enums\OrderStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])" :value="request('status')" placeholder="All statuses" class="[&_label]:text-xs" />
        <x-select name="event" label="Event" :options="$events" :value="request('event')" placeholder="All events" class="[&_label]:text-xs" />
        <x-select name="package" label="Package" :options="$packages" :value="request('package')" placeholder="All packages" class="[&_label]:text-xs" />
    </x-admin.filters>

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-admin.stat label="Paid revenue" :value="money($paid->revenue)" hint="For the current filter" />
        <x-admin.stat label="Paid bookings" :value="number_format($paid->bookings)" />
        <x-admin.stat label="Seats sold" :value="number_format($paid->seats)" />
        <x-admin.stat label="Matching records" :value="number_format($bookings->total())" hint="All statuses" />
    </div>

    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Contact</th>
                        <th>Event / package</th>
                        <th class="text-right"><x-admin.sort-link column="quantity" label="Qty" /></th>
                        <th class="text-right"><x-admin.sort-link column="amount" label="Amount" /></th>
                        <th>Status</th>
                        <th><x-admin.sort-link column="created_at" label="Date" /></th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookings as $booking)
                        <tr>
                            <td class="font-mono text-xs"><a href="{{ route('admin.bookings.show', $booking) }}" class="hover:text-gold-700">{{ $booking->reference }}</a></td>
                            <td>
                                <span class="font-medium">{{ $booking->contact_name }}</span>
                                <span class="block text-xs text-ink-400">{{ $booking->contact_email }}@if ($booking->organization) · {{ $booking->organization }}@endif</span>
                            </td>
                            <td class="text-sm">
                                {{ $booking->eventPackage?->name ?? '—' }}
                                <span class="block text-xs text-ink-400">{{ $booking->awardEvent?->name }}</span>
                            </td>
                            <td class="text-right tabular-nums">{{ $booking->quantity }} <span class="block text-xs text-ink-400">{{ $booking->seats }} seats</span></td>
                            <td class="text-right tabular-nums">{{ money($booking->amount, $booking->currency) }}</td>
                            <td><x-status-badge :status="$booking->status" /></td>
                            <td class="text-xs whitespace-nowrap">{{ $booking->created_at?->format('j M Y, g:ia') }}</td>
                            <td class="text-right"><a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-outline btn-sm">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><x-empty-state title="No bookings found" message="Try a different search or filter." class="border-0" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $bookings->links() }}</div>
</x-layouts.admin>
