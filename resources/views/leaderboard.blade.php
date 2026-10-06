<x-layouts.app :title="'Leaderboard'.($event ? ' — '.$event->name : '')" :description="$event ? 'Live results and rankings for '.$event->name.'.' : null">
    <x-page-header :eyebrow="$event?->name" title="Leaderboard" :subtitle="$visible ? 'Rankings update automatically as votes come in.' : null">
        @if ($events->count() > 1)
            <form method="GET" class="mt-6 max-w-xs">
                <label for="event" class="sr-only">Event</label>
                <select id="event" name="event" onchange="this.form.submit()" class="input border-white/20 bg-white/10 text-white">
                    @foreach ($events as $e)
                        <option value="{{ $e->slug }}" @selected($event?->id === $e->id) class="text-ink-900">{{ $e->name }}</option>
                    @endforeach
                </select>
            </form>
        @endif
    </x-page-header>

    <div class="container-page mt-10">
        @if (! $event)
            <x-empty-state title="No events yet" />
        @elseif (! $visible)
            <x-empty-state title="Results are hidden" :message="$event->results_visibility === \App\Enums\ResultsVisibility::AfterVoting ? 'Results will be published here once voting closes.' : 'The organisers have chosen to keep vote counts private. Winners will be announced at the awards night.'">
                <a href="{{ route('voting.event', $event) }}" class="btn btn-gold">Go vote</a>
            </x-empty-state>
        @else
            <div class="grid gap-6 lg:grid-cols-2">
                @foreach ($board as $row)
                    <section class="card overflow-hidden">
                        <div class="flex items-center justify-between gap-4 border-b border-ink-100 px-5 py-4">
                            <h2 class="font-sans text-lg font-semibold">{{ $row['category']->name }}</h2>
                            <span class="shrink-0 text-xs text-ink-500">{{ number_format($row['total']) }} votes</span>
                        </div>
                        @if ($row['nominees']->isEmpty())
                            <p class="p-5 text-sm text-ink-500">No nominees yet.</p>
                        @else
                            <ol class="divide-y divide-ink-50">
                                @foreach ($row['nominees'] as $nominee)
                                    <li class="flex items-center gap-3 px-5 py-3">
                                        <span @class([
                                            'grid size-8 shrink-0 place-items-center rounded-full text-sm font-bold',
                                            'bg-gold-400 text-ink-950' => $nominee->rank === 1,
                                            'bg-ink-200 text-ink-800' => $nominee->rank === 2,
                                            'bg-gold-100 text-gold-800' => $nominee->rank === 3,
                                            'bg-ink-50 text-ink-500' => $nominee->rank > 3,
                                        ])>{{ $nominee->rank }}</span>
                                        <a href="{{ route('nominees.show', $nominee->slug) }}" class="min-w-0 flex-1">
                                            <span class="block truncate font-medium hover:text-gold-700">{{ $nominee->name }}</span>
                                            <span class="mt-1 block h-1.5 overflow-hidden rounded-full bg-ink-50"><span class="block h-full rounded-full bg-gold-400" style="width: {{ $nominee->share }}%"></span></span>
                                        </a>
                                        <span class="shrink-0 text-right">
                                            <span class="block font-semibold tabular-nums">{{ number_format($nominee->total_votes) }}</span>
                                            <span class="block text-xs text-ink-400">{{ $nominee->share }}%</span>
                                        </span>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </section>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.app>
