<x-layouts.admin title="Vote ledger">
    <x-admin.page-header title="Vote ledger" subtitle="Every vote with its origin. Entries are never edited — corrections are separate adjustment entries.">
        <x-slot:actions>
            @can('votes.adjust')
                <a href="{{ route('admin.votes.adjust') }}" class="btn btn-outline">Adjust votes</a>
            @endcan
            <a href="{{ route('admin.votes.export', request()->query()) }}" class="btn btn-ink">Export CSV</a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.filters :dates="true" placeholder="Nominee, voter email, order or payment ref…">
        <x-select name="event" label="Event" :options="$events->pluck('name', 'id')" :value="request('event')" placeholder="All events" class="[&_label]:text-xs" />
        <x-select name="type" label="Type" :options="collect(\App\Enums\VoteType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])" :value="request('type')" placeholder="All types" class="[&_label]:text-xs" />
        <x-select name="status" label="Status" :options="['valid' => 'Valid', 'invalidated' => 'Invalidated']" :value="request('status')" placeholder="Any status" class="[&_label]:text-xs" />
        <x-input name="ip" label="IP address" :value="request('ip')" class="[&_label]:text-xs" />
    </x-admin.filters>

    <p class="mb-3 text-sm text-ink-500">{{ number_format($summary->entries) }} entries · <strong class="text-ink-800">{{ number_format($summary->valid_total) }}</strong> valid votes in this view</p>

    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nominee</th>
                        <th>Type</th>
                        <th class="text-right"><x-admin.sort-link column="votes" label="Votes" /></th>
                        <th>Voter</th>
                        <th>Origin</th>
                        <th>IP</th>
                        <th><x-admin.sort-link column="created_at" label="Date" /></th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($votes as $vote)
                        <tr @class(['opacity-60' => ! $vote->isValid()])>
                            <td class="text-xs text-ink-400">{{ $vote->id }}</td>
                            <td><span class="font-medium text-ink-900">{{ $vote->nominee->name }}</span><span class="block text-xs text-ink-400">{{ $vote->category->name }} · {{ $vote->awardEvent->name }}</span></td>
                            <td>{{ $vote->vote_type->label() }}</td>
                            <td class="text-right font-semibold tabular-nums">{{ $vote->votes > 0 && $vote->vote_type === \App\Enums\VoteType::AdminAdjustment ? '+' : '' }}{{ number_format($vote->votes) }}</td>
                            <td class="text-xs">
                                @if ($vote->user)
                                    <a href="{{ auth()->user()->can('voters.manage') && ! $vote->user->isStaff() ? route('admin.voters.show', $vote->user) : '#' }}" class="font-medium text-ink-800 hover:text-gold-700">{{ $vote->user->name }}</a><br>{{ $vote->user->email }}
                                @elseif ($vote->admin)
                                    <span class="text-ink-500">by {{ $vote->admin->name }}</span>
                                @else
                                    <span class="text-ink-400">Guest</span>
                                @endif
                            </td>
                            <td class="text-xs">
                                @if ($vote->voteOrder)
                                    @can('orders.view')<a href="{{ route('admin.orders.show', $vote->voteOrder) }}" class="font-mono text-ink-800 hover:text-gold-700">{{ $vote->voteOrder->reference }}</a>@else<span class="font-mono">{{ $vote->voteOrder->reference }}</span>@endcan
                                    <br><span class="text-ink-400">{{ money($vote->voteOrder->amount, $vote->voteOrder->currency) }}</span>
                                @elseif ($vote->reason)
                                    <span class="line-clamp-2 max-w-48" title="{{ $vote->reason }}">{{ $vote->reason }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="font-mono text-xs">
                                @if ($vote->ip_address)<a href="{{ request()->fullUrlWithQuery(['ip' => $vote->ip_address, 'page' => null]) }}" class="hover:text-gold-700">{{ $vote->ip_address }}</a>@else — @endif
                            </td>
                            <td class="text-xs whitespace-nowrap">{{ $vote->created_at->format('j M Y, g:i:sa') }}</td>
                            <td><x-status-badge :status="$vote->status" /></td>
                            <td class="text-right">
                                @can('votes.invalidate')
                                    @if ($vote->isValid())
                                        <form method="POST" action="{{ route('admin.votes.invalidate', $vote) }}" x-data @submit.prevent="const r = prompt('Reason for invalidating this entry?'); if (r) { $refs.reason.value = r; $el.submit() }">
                                            @csrf
                                            <input type="hidden" name="reason" x-ref="reason">
                                            <button class="text-xs font-medium text-rose-600 hover:text-rose-800">Invalidate</button>
                                        </form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10"><x-empty-state title="No votes match these filters" class="border-0" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $votes->links() }}</div>
</x-layouts.admin>
