<x-layouts.admin title="Table packages">
    <x-admin.page-header title="Table packages" subtitle="Tables and seats guests can book for the awards night.">
        <x-slot:actions>
            <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline">Bookings</a>
            <a href="{{ route('admin.event-packages.create', array_filter(['event' => request('event')])) }}" class="btn btn-gold">New package</a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.filters placeholder="Search packages…">
        <x-select name="event" label="Event" :options="$events" :value="request('event')" placeholder="All events" class="[&_label]:text-xs" />
        <x-select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="request('status')" placeholder="All statuses" class="[&_label]:text-xs" />
    </x-admin.filters>

    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th><x-admin.sort-link column="name" label="Package" /></th>
                        <th>Event</th>
                        <th class="text-right"><x-admin.sort-link column="price" label="Price" /></th>
                        <th class="text-right">Seats</th>
                        <th class="text-right"><x-admin.sort-link column="quantity_sold" label="Sold / available" /></th>
                        <th class="text-right"><x-admin.sort-link column="sort_order" label="Order" /></th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($packages as $package)
                        <tr>
                            <td>
                                <a href="{{ route('admin.event-packages.edit', $package) }}" class="font-semibold text-ink-900 hover:text-gold-700">{{ $package->name }}</a>
                                @if (count($package->benefits ?? []))<span class="block text-xs text-ink-400">{{ count($package->benefits) }} {{ Str::plural('benefit', count($package->benefits)) }}</span>@endif
                            </td>
                            <td class="text-sm">{{ $package->awardEvent?->name ?? '—' }}</td>
                            <td class="text-right font-semibold tabular-nums whitespace-nowrap">{{ money($package->price, $package->awardEvent?->currency()) }}</td>
                            <td class="text-right tabular-nums">{{ number_format($package->seats) }}</td>
                            <td class="text-right tabular-nums whitespace-nowrap">
                                {{ number_format($package->quantity_sold) }} / {{ number_format($package->quantity_available) }}
                                @if ($package->quantity_available > 0 && $package->remaining() === 0)
                                    <span class="badge ml-1 bg-rose-50 text-rose-700">Sold out</span>
                                @endif
                            </td>
                            <td class="text-right tabular-nums">{{ $package->sort_order }}</td>
                            <td><x-status-badge :status="$package->status" /></td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('admin.event-packages.edit', $package) }}" class="text-sm font-medium text-ink-600 hover:text-ink-900">Edit</a>
                                <span class="mx-1 text-ink-200">|</span>
                                <x-admin.confirm-form :action="route('admin.event-packages.destroy', $package)" method="DELETE"
                                    :confirm="'Delete the package '.$package->name.'?'" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><x-empty-state title="No table packages found" message="Add a package, or adjust the filters above." class="border-0" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $packages->links() }}</div>
</x-layouts.admin>
