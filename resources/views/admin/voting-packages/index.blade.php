@php
    $currency = $event?->currency();
@endphp

<x-layouts.admin title="Voting packages">
    <x-admin.page-header title="Voting packages" :subtitle="$event ? 'Vote bundles voters can buy for '.$event->name.'.' : 'Vote bundles voters can buy for an event.'">
        <x-slot:actions>
            <a href="{{ route('admin.voting-packages.create', array_filter(['event' => $event?->id])) }}" class="btn btn-gold">New package</a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.filters placeholder="Search packages…">
        <x-select name="event" label="Event" :options="$events" :value="$event?->id" class="[&_label]:text-xs" />
        <x-select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="request('status')" placeholder="All statuses" class="[&_label]:text-xs" />
    </x-admin.filters>

    @if (! $event)
        <x-empty-state title="No events yet" message="Create an award event before adding voting packages.">
            <a href="{{ route('admin.events.create') }}" class="btn btn-gold">New event</a>
        </x-empty-state>
    @else
        <div class="card overflow-hidden">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="w-20"><x-admin.sort-link column="sort_order" label="Order" /></th>
                            <th><x-admin.sort-link column="name" label="Package" /></th>
                            <th class="text-right"><x-admin.sort-link column="amount" label="Price" /></th>
                            <th class="text-right"><x-admin.sort-link column="votes" label="Votes" /></th>
                            <th class="text-right">Per vote</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($packages as $package)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-1">
                                        <span class="w-6 text-sm tabular-nums text-ink-500">{{ $package->sort_order }}</span>
                                        @foreach (['up' => ['↑', 'Move up'], 'down' => ['↓', 'Move down']] as $direction => [$arrow, $title])
                                            @if (! empty($moves[$package->id][$direction]))
                                                <form method="POST" action="{{ route('admin.voting-packages.reorder') }}">
                                                    @csrf
                                                    <input type="hidden" name="award_event_id" value="{{ $event->id }}">
                                                    @foreach ($moves[$package->id][$direction] as $id)
                                                        <input type="hidden" name="order[]" value="{{ $id }}">
                                                    @endforeach
                                                    <button type="submit" class="btn btn-outline btn-sm px-2" title="{{ $title }}" aria-label="{{ $title }}: {{ $package->name }}">{{ $arrow }}</button>
                                                </form>
                                            @endif
                                        @endforeach
                                    </div>
                                </td>
                                <td>
                                    <a href="{{ route('admin.voting-packages.edit', $package) }}" class="font-semibold text-ink-900 hover:text-gold-700">{{ $package->name }}</a>
                                    @if ($package->description)<span class="block text-xs text-ink-400">{{ $package->description }}</span>@endif
                                </td>
                                <td class="text-right font-semibold tabular-nums whitespace-nowrap">{{ money($package->amount, $currency) }}</td>
                                <td class="text-right tabular-nums">{{ number_format($package->votes) }}</td>
                                <td class="text-right text-sm tabular-nums whitespace-nowrap text-ink-500">{{ money(round((float) $package->amount / max(1, $package->votes), 2), $currency) }}</td>
                                <td><x-status-badge :status="$package->status" /></td>
                                <td class="text-right whitespace-nowrap">
                                    <a href="{{ route('admin.voting-packages.edit', $package) }}" class="text-sm font-medium text-ink-600 hover:text-ink-900">Edit</a>
                                    <span class="mx-1 text-ink-200">|</span>
                                    <x-admin.confirm-form :action="route('admin.voting-packages.destroy', $package)" method="DELETE"
                                        :confirm="'Delete the package '.$package->name.'?'" />
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><x-empty-state title="No packages found" message="Add a voting package for this event, or adjust the filters above." class="border-0" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <p class="hint mt-3">Use the arrows to change the order packages are shown to voters.</p>
        <div class="mt-6">{{ $packages->links() }}</div>
    @endif
</x-layouts.admin>
