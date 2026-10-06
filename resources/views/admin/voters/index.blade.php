<x-layouts.admin title="Voters">
    <x-admin.page-header title="Voters" subtitle="Registered voter accounts. Suspended or inactive voters are signed out and cannot vote." />

    <x-admin.filters placeholder="Name, email or phone…" :dates="true">
        <x-select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended']" :value="request('status')" placeholder="All statuses" class="[&_label]:text-xs" />
        <x-select name="verified" label="Email verified" :options="['yes' => 'Verified', 'no' => 'Not verified']" :value="request('verified')" placeholder="Any" class="[&_label]:text-xs" />
    </x-admin.filters>

    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th><x-admin.sort-link column="name" label="Voter" /></th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th class="text-right">Vote records</th>
                        <th class="text-right">Valid votes</th>
                        <th><x-admin.sort-link column="created_at" label="Registered" /></th>
                        <th><x-admin.sort-link column="last_login_at" label="Last sign-in" /></th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($voters as $voter)
                        <tr>
                            <td>
                                <a href="{{ route('admin.voters.show', $voter) }}" class="font-semibold text-ink-900 hover:text-gold-700">{{ $voter->name }}</a>
                                <span class="block text-xs text-ink-400">
                                    {{ $voter->email }}
                                    @if ($voter->email_verified_at)
                                        <span class="text-emerald-600" title="Verified">✓</span>
                                    @else
                                        <span class="text-amber-600">(unverified)</span>
                                    @endif
                                </span>
                            </td>
                            <td class="text-sm whitespace-nowrap">{{ $voter->phone ?: '—' }}</td>
                            <td><x-status-badge :status="$voter->status" /></td>
                            <td class="text-right tabular-nums">{{ number_format($voter->votes_count) }}</td>
                            <td class="text-right tabular-nums">{{ number_format((int) $voter->valid_votes) }}</td>
                            <td class="text-xs whitespace-nowrap">{{ $voter->created_at?->format('j M Y, g:ia') }}</td>
                            <td class="text-xs whitespace-nowrap">{{ $voter->last_login_at?->format('j M Y, g:ia') ?? 'Never' }}</td>
                            <td class="text-right">
                                <a href="{{ route('admin.voters.show', $voter) }}" class="btn btn-outline btn-sm">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><x-empty-state title="No voters found" message="Try a different search or filter." class="border-0" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $voters->links() }}</div>
</x-layouts.admin>
