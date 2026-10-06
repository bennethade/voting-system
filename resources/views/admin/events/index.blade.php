<x-layouts.admin title="Award events">
    <x-admin.page-header title="Award events" subtitle="Each event has its own categories, nominees, packages and voting window.">
        <x-slot:actions>
            <a href="{{ route('admin.events.create') }}" class="btn btn-gold">New event</a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.filters placeholder="Search events…">
        <x-select name="status" label="Status" :options="collect(\App\Enums\EventStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])" :value="request('status')" placeholder="All statuses" class="[&_label]:text-xs" />
    </x-admin.filters>

    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th><x-admin.sort-link column="name" label="Event" /></th>
                        <th>Status</th>
                        <th><x-admin.sort-link column="voting_end_at" label="Voting window" /></th>
                        <th>Results</th>
                        <th class="text-right">Categories</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($events as $event)
                        <tr>
                            <td>
                                <a href="{{ route('admin.events.edit', $event) }}" class="font-semibold text-ink-900 hover:text-gold-700">{{ $event->name }}</a>
                                @if ($event->is_featured)<span class="badge ml-1 bg-gold-100 text-gold-800">Featured</span>@endif
                                <span class="block text-xs text-ink-400">/{{ $event->slug }}</span>
                            </td>
                            <td>
                                <x-status-badge :status="$event->status" />
                                @if ($event->isResultsLocked())<span class="badge mt-1 block w-fit bg-ink-900 text-white">Results locked</span>@endif
                            </td>
                            <td class="text-xs whitespace-nowrap">
                                {{ $event->voting_start_at?->format('j M Y, g:ia') ?? '—' }}<br>
                                → {{ $event->voting_end_at?->format('j M Y, g:ia') ?? '—' }}
                            </td>
                            <td><x-status-badge :status="$event->results_visibility" /></td>
                            <td class="text-right tabular-nums">{{ $event->categories_count }}</td>
                            <td class="text-right">
                                <div x-data="{ open: false }" class="relative inline-block text-left">
                                    <button type="button" @click="open = !open" @click.outside="open = false" class="btn btn-outline btn-sm">Manage ▾</button>
                                    <div x-cloak x-show="open" class="absolute right-0 z-20 mt-1 w-52 rounded-xl bg-white py-1 text-sm shadow-xl ring-1 ring-ink-100">
                                        <a href="{{ route('admin.events.edit', $event) }}" class="block px-4 py-2 hover:bg-ink-50">Edit details</a>
                                        <a href="{{ route('admin.categories.index', ['event' => $event->id]) }}" class="block px-4 py-2 hover:bg-ink-50">Categories</a>
                                        <a href="{{ route('admin.voting-packages.index', ['event' => $event->id]) }}" class="block px-4 py-2 hover:bg-ink-50">Voting packages</a>
                                        <a href="{{ route('admin.nominees.index', ['event' => $event->id]) }}" class="block px-4 py-2 hover:bg-ink-50">Nominees</a>
                                        <a href="{{ route('admin.event-packages.index', ['event' => $event->id]) }}" class="block px-4 py-2 hover:bg-ink-50">Table packages</a>
                                        <div class="my-1 border-t border-ink-100"></div>
                                        @foreach (['voting' => 'Open voting', 'paused' => 'Pause voting', 'closed' => 'Close voting', 'completed' => 'Mark completed'] as $status => $label)
                                            @if ($event->status->value !== $status)
                                                <form method="POST" action="{{ route('admin.events.transition', $event) }}" onsubmit="return confirm(@js($label.' for '.$event->name.'?'))">
                                                    @csrf
                                                    <input type="hidden" name="status" value="{{ $status }}">
                                                    <button class="block w-full px-4 py-2 text-left hover:bg-ink-50">{{ $label }}</button>
                                                </form>
                                            @endif
                                        @endforeach
                                        <div class="my-1 border-t border-ink-100"></div>
                                        @if (! $event->isResultsLocked())
                                            <form method="POST" action="{{ route('admin.events.lock', $event) }}" onsubmit="return confirm('Lock final results? Voting will close and vote records will be frozen.')">
                                                @csrf
                                                <button class="block w-full px-4 py-2 text-left font-medium text-rose-700 hover:bg-rose-50">Lock final results</button>
                                            </form>
                                        @elseif (auth()->user()->isSuperAdmin())
                                            <form method="POST" action="{{ route('admin.events.unlock', $event) }}" onsubmit="return confirm('Unlock results?')">
                                                @csrf
                                                <button class="block w-full px-4 py-2 text-left hover:bg-ink-50">Unlock results</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty-state title="No events yet" message="Create your first award event to get started." class="border-0" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $events->links() }}</div>
</x-layouts.admin>
