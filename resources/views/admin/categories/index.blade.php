<x-layouts.admin title="Categories">
    <x-admin.page-header title="Categories" subtitle="Award categories belong to an event and hold its nominees.">
        <x-slot:actions>
            <a href="{{ route('admin.categories.create', array_filter(['event' => request('event')])) }}" class="btn btn-gold">New category</a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.filters placeholder="Search categories…">
        <x-select name="event" label="Event" :options="$events" :value="request('event')" placeholder="All events" class="[&_label]:text-xs" />
        <x-select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="request('status')" placeholder="All statuses" class="[&_label]:text-xs" />
    </x-admin.filters>

    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th><x-admin.sort-link column="name" label="Category" /></th>
                        <th>Event</th>
                        <th class="text-right">Nominees</th>
                        <th class="text-right"><x-admin.sort-link column="sort_order" label="Order" /></th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    @if ($category->image)
                                        <img src="{{ media_url($category->image) }}" alt="" class="size-10 shrink-0 rounded-lg object-cover">
                                    @endif
                                    <div>
                                        <a href="{{ route('admin.categories.edit', $category) }}" class="font-semibold text-ink-900 hover:text-gold-700">{{ $category->name }}</a>
                                        <span class="block text-xs text-ink-400">/{{ $category->slug }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="text-sm">{{ $category->awardEvent?->name ?? '—' }}</td>
                            <td class="text-right tabular-nums">
                                <a href="{{ route('admin.nominees.index', ['category' => $category->id]) }}" class="hover:text-gold-700">{{ number_format($category->nominees_count) }}</a>
                            </td>
                            <td class="text-right tabular-nums">{{ $category->sort_order }}</td>
                            <td><x-status-badge :status="$category->status" /></td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('admin.nominees.index', ['category' => $category->id]) }}" class="text-sm font-medium text-ink-600 hover:text-ink-900">Nominees</a>
                                <span class="mx-1 text-ink-200">|</span>
                                <a href="{{ route('admin.categories.edit', $category) }}" class="text-sm font-medium text-ink-600 hover:text-ink-900">Edit</a>
                                <span class="mx-1 text-ink-200">|</span>
                                <x-admin.confirm-form :action="route('admin.categories.destroy', $category)" method="DELETE"
                                    :confirm="'Delete the category '.$category->name.' and all of its nominees? This cannot be undone.'" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty-state title="No categories found" message="Create a category, or adjust the filters above." class="border-0" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $categories->links() }}</div>
</x-layouts.admin>
