<x-layouts.admin title="Leaderboard">
    <x-admin.page-header title="Leaderboard" :subtitle="$event ? $event->name.' · public results: '.$event->results_visibility->label() : null">
        <x-slot:actions>
            <form method="GET">
                <select name="event" class="input w-auto" onchange="this.form.submit()" aria-label="Event">
                    @foreach ($events as $e)
                        <option value="{{ $e->id }}" @selected($event?->id === $e->id)>{{ $e->name }}</option>
                    @endforeach
                </select>
            </form>
        </x-slot:actions>
    </x-admin.page-header>

    @if (! $event)
        <x-empty-state title="No events yet" />
    @else
        <div class="grid gap-6 xl:grid-cols-2">
            @foreach ($board as $row)
                <section class="card overflow-hidden">
                    <div class="flex items-center justify-between border-b border-ink-100 px-5 py-4">
                        <h3 class="font-sans text-base font-semibold">{{ $row['category']->name }}</h3>
                        <span class="text-xs text-ink-500">{{ number_format($row['total']) }} votes</span>
                    </div>
                    <table class="table">
                        <tbody>
                            @forelse ($row['nominees'] as $nominee)
                                <tr>
                                    <td class="w-10 font-semibold text-ink-400">{{ $nominee->rank }}</td>
                                    <td class="font-medium text-ink-900">{{ $nominee->name }}</td>
                                    <td class="w-1/3"><span class="block h-1.5 rounded-full bg-ink-50"><span class="block h-full rounded-full bg-gold-400" style="width: {{ $nominee->share }}%"></span></span></td>
                                    <td class="text-right font-semibold tabular-nums">{{ number_format($nominee->total_votes) }}</td>
                                    <td class="text-right text-xs text-ink-400">{{ $nominee->share }}%</td>
                                    @can('votes.view')
                                        <td class="text-right"><a href="{{ route('admin.votes.index', ['nominee' => $nominee->id]) }}" class="text-xs font-medium text-gold-700">Ledger</a></td>
                                    @endcan
                                </tr>
                            @empty
                                <tr><td class="py-6 text-center text-ink-400">No nominees.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </section>
            @endforeach
        </div>
        <p class="mt-6 text-xs text-ink-400">Totals come from the cached counter on each nominee. Run <code class="rounded bg-ink-100 px-1">php artisan votes:recalculate --dry-run</code> to verify them against the ledger.</p>
    @endif
</x-layouts.admin>
