<x-layouts.admin title="FAQs">
    <x-admin.page-header title="FAQs" subtitle="Questions and answers shown on the public FAQ page.">
        <x-slot:actions>
            <a href="{{ route('admin.faqs.create') }}" class="btn btn-gold">New FAQ</a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.filters placeholder="Search questions and answers…">
        <x-select name="category" label="Category" :options="$categories" :value="request('category')" placeholder="All categories" class="[&_label]:text-xs" />
        <x-select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="request('status')" placeholder="All statuses" class="[&_label]:text-xs" />
    </x-admin.filters>

    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th><x-admin.sort-link column="question" label="Question" /></th>
                        <th><x-admin.sort-link column="category" label="Category" /></th>
                        <th>Status</th>
                        <th class="text-right"><x-admin.sort-link column="sort_order" label="Order" /></th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($faqs as $faq)
                        <tr>
                            <td class="max-w-xl">
                                <a href="{{ route('admin.faqs.edit', $faq) }}" class="font-semibold text-ink-900 hover:text-gold-700">{{ $faq->question }}</a>
                                <span class="mt-0.5 block line-clamp-2 text-xs text-ink-500">{{ $faq->answer }}</span>
                            </td>
                            <td class="text-sm">{{ $faq->category ?? '—' }}</td>
                            <td><x-status-badge :status="$faq->status" /></td>
                            <td class="text-right tabular-nums">{{ $faq->sort_order }}</td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('admin.faqs.edit', $faq) }}" class="text-sm font-medium text-ink-700 hover:text-ink-900">Edit</a>
                                <span class="mx-1 text-ink-200">|</span>
                                <x-admin.confirm-form :action="route('admin.faqs.destroy', $faq)" method="DELETE" confirm="Delete this FAQ?" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty-state title="No FAQs found" message="Add a question or adjust your filters." class="border-0" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $faqs->links() }}</div>
</x-layouts.admin>
