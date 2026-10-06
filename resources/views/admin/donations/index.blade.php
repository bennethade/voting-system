<x-layouts.admin title="Donations">
    <x-admin.page-header title="Donations" subtitle="Donations received through the site, with their payment status.">
        <x-slot:actions>
            <a href="{{ route('admin.donations.export', request()->query()) }}" class="btn btn-outline">Export CSV</a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.filters placeholder="Reference, donor, email…" :dates="true">
        <x-select name="status" label="Status" :options="collect(\App\Enums\OrderStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])" :value="request('status')" placeholder="All statuses" class="[&_label]:text-xs" />
        <x-select name="event" label="Event" :options="$events" :value="request('event')" placeholder="All events" class="[&_label]:text-xs" />
    </x-admin.filters>

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-admin.stat label="Paid donations" :value="money($paidTotal)" hint="For the current filter" />
        <x-admin.stat label="Paid count" :value="number_format($paidCount)" />
        <x-admin.stat label="Matching records" :value="number_format($donations->total())" hint="All statuses" />
    </div>

    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Donor</th>
                        <th>Event</th>
                        <th class="text-right"><x-admin.sort-link column="amount" label="Amount" /></th>
                        <th>Status</th>
                        <th><x-admin.sort-link column="created_at" label="Date" /></th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($donations as $donation)
                        <tr>
                            <td class="font-mono text-xs"><a href="{{ route('admin.donations.show', $donation) }}" class="hover:text-gold-700">{{ $donation->reference }}</a></td>
                            <td>
                                <span class="font-medium">{{ $donation->donor_name }}</span>
                                @if ($donation->is_anonymous)<span class="badge ml-1 bg-ink-50 text-ink-600">Anonymous</span>@endif
                                <span class="block text-xs text-ink-400">{{ $donation->email }}</span>
                            </td>
                            <td class="text-sm">{{ $donation->awardEvent?->name ?? 'General' }}</td>
                            <td class="text-right tabular-nums">{{ money($donation->amount, $donation->currency) }}</td>
                            <td><x-status-badge :status="$donation->status" /></td>
                            <td class="text-xs whitespace-nowrap">{{ $donation->created_at?->format('j M Y, g:ia') }}</td>
                            <td class="text-right"><a href="{{ route('admin.donations.show', $donation) }}" class="btn btn-outline btn-sm">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty-state title="No donations found" message="Try a different search or filter." class="border-0" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $donations->links() }}</div>
</x-layouts.admin>
