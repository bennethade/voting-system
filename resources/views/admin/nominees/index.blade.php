<x-layouts.admin title="Nominees">
    <x-admin.page-header title="Nominees" subtitle="Nominees compete within a category. Vote totals can only change through the vote ledger.">
        <x-slot:actions>
            <a href="{{ route('admin.nominees.create', array_filter(['category' => request('category')])) }}" class="btn btn-gold">New nominee</a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.filters placeholder="Search nominees…">
        <x-select name="event" label="Event" :options="$events" :value="request('event')" placeholder="All events" class="[&_label]:text-xs" />
        <x-select name="category" label="Category" :options="$categories" :value="request('category')" placeholder="All categories" class="[&_label]:text-xs" />
        <x-select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="request('status')" placeholder="All statuses" class="[&_label]:text-xs" />
    </x-admin.filters>

    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th class="w-16"><span class="sr-only">Image</span></th>
                        <th><x-admin.sort-link column="name" label="Nominee" /></th>
                        <th>Category</th>
                        <th class="text-right"><x-admin.sort-link column="total_votes" label="Votes" /></th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($nominees as $nominee)
                        <tr>
                            <td>
                                <div class="grid size-11 place-items-center overflow-hidden rounded-xl bg-ink-50 text-xs font-semibold text-ink-400">
                                    @if ($nominee->image)
                                        <img src="{{ media_url($nominee->image) }}" alt="" class="size-full object-cover" loading="lazy">
                                    @else
                                        {{ initials($nominee->name) }}
                                    @endif
                                </div>
                            </td>
                            <td>
                                <a href="{{ route('admin.nominees.edit', $nominee) }}" class="font-semibold text-ink-900 hover:text-gold-700">{{ $nominee->name }}</a>
                                <span class="block text-xs text-ink-400">/{{ $nominee->slug }}</span>
                            </td>
                            <td class="text-sm">
                                {{ $nominee->category?->name ?? '—' }}
                                <span class="block text-xs text-ink-400">{{ $nominee->category?->awardEvent?->name }}</span>
                            </td>
                            <td class="text-right font-semibold tabular-nums">{{ number_format($nominee->total_votes) }}</td>
                            <td><x-status-badge :status="$nominee->status" /></td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('admin.nominees.edit', $nominee) }}" class="text-sm font-medium text-ink-600 hover:text-ink-900">Edit</a>
                                <span class="mx-1 text-ink-200">|</span>
                                <x-admin.confirm-form :action="route('admin.nominees.destroy', $nominee)" method="DELETE"
                                    :confirm="'Delete the nominee '.$nominee->name.'? This cannot be undone.'" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty-state title="No nominees found" message="Add a nominee, or adjust the filters above." class="border-0" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $nominees->links() }}</div>
</x-layouts.admin>
