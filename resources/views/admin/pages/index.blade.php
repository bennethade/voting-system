<x-layouts.admin title="Pages">
    <x-admin.page-header title="Pages" subtitle="About, legal and other content pages. System pages can be edited but not deleted.">
        <x-slot:actions>
            <a href="{{ route('admin.pages.create') }}" class="btn btn-gold">New page</a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.filters placeholder="Search title or slug…">
        <x-select name="status" label="Status" :options="['published' => 'Published', 'draft' => 'Draft']" :value="request('status')" placeholder="All statuses" class="[&_label]:text-xs" />
    </x-admin.filters>

    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th><x-admin.sort-link column="title" label="Page" /></th>
                        <th>Status</th>
                        <th>Public URL</th>
                        <th><x-admin.sort-link column="updated_at" label="Last updated" /></th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pages as $page)
                        <tr>
                            <td>
                                <a href="{{ route('admin.pages.edit', $page) }}" class="font-semibold text-ink-900 hover:text-gold-700">{{ $page->title }}</a>
                                @if ($page->is_system)<span class="badge ml-1 bg-ink-900 text-white">System</span>@endif
                                <span class="block text-xs text-ink-400">/{{ $page->slug }}</span>
                            </td>
                            <td><x-status-badge :status="$page->status" /></td>
                            <td class="text-xs">
                                <a href="{{ $publicUrls[$page->id] }}" target="_blank" rel="noopener" class="text-gold-700 hover:underline">{{ $publicUrls[$page->id] }}</a>
                            </td>
                            <td class="text-xs whitespace-nowrap">{{ $page->updated_at?->format('j M Y, g:ia') }}</td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('admin.pages.edit', $page) }}" class="text-sm font-medium text-ink-700 hover:text-ink-900">Edit</a>
                                @unless ($page->is_system)
                                    <span class="mx-1 text-ink-200">|</span>
                                    <x-admin.confirm-form :action="route('admin.pages.destroy', $page)" method="DELETE" :confirm="'Delete the page '.$page->title.'?'" />
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty-state title="No pages found" message="Create a page or adjust your filters." class="border-0" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $pages->links() }}</div>
</x-layouts.admin>
