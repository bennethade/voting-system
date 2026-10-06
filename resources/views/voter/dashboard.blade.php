<x-layouts.voter :title="'Hi, '.\Illuminate\Support\Str::before($user->name, ' ')">
    <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        @foreach ([
            ['Total votes cast', number_format($stats->total)],
            ['Free votes', number_format($stats->free)],
            ['Paid votes', number_format($stats->paid)],
            ['Amount spent', money($spent)],
        ] as [$label, $value])
            <div class="card p-4 sm:p-5">
                <p class="text-xs text-ink-500 sm:text-sm">{{ $label }}</p>
                <p class="mt-1 font-display text-2xl font-semibold sm:text-3xl">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    @if ($event?->isVotingOpen())
        <div class="hero-glow mt-6 flex flex-col gap-4 rounded-3xl p-6 text-white sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="eyebrow text-gold-300">Voting is open</p>
                <p class="mt-1 text-xl font-semibold">{{ $event->name }}</p>
                @if ($event->voting_end_at)<p class="mt-1 text-sm text-ink-300">Closes {{ $event->voting_end_at->diffForHumans() }}</p>@endif
            </div>
            <a href="{{ route('voting.event', $event) }}" class="btn btn-gold">Vote now</a>
        </div>
    @endif

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="card">
            <div class="flex items-center justify-between border-b border-ink-100 px-5 py-4">
                <h2 class="font-sans text-base font-semibold">Recent votes</h2>
                <a href="{{ route('voter.votes') }}" class="text-sm font-medium text-gold-700">View all</a>
            </div>
            @forelse ($recentVotes as $vote)
                <div class="flex items-center justify-between gap-3 border-b border-ink-50 px-5 py-3 last:border-0">
                    <div class="min-w-0">
                        <a href="{{ route('nominees.show', $vote->nominee) }}" class="block truncate font-medium hover:text-gold-700">{{ $vote->nominee->name }}</a>
                        <p class="text-xs text-ink-500">{{ $vote->category->name }} · {{ $vote->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="font-semibold">{{ number_format($vote->votes) }}</p>
                        <p class="text-xs text-ink-500">{{ $vote->vote_type->label() }}</p>
                    </div>
                </div>
            @empty
                <p class="p-5 text-sm text-ink-500">You haven't voted yet.</p>
            @endforelse
        </section>

        <section class="card">
            <div class="flex items-center justify-between border-b border-ink-100 px-5 py-4">
                <h2 class="font-sans text-base font-semibold">Recent orders</h2>
                <a href="{{ route('voter.orders') }}" class="text-sm font-medium text-gold-700">View all</a>
            </div>
            @forelse ($recentOrders as $order)
                <div class="flex items-center justify-between gap-3 border-b border-ink-50 px-5 py-3 last:border-0">
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ number_format($order->votes) }} votes · {{ $order->nominee->name }}</p>
                        <p class="font-mono text-xs text-ink-500">{{ $order->reference }}</p>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="font-semibold">{{ money($order->amount, $order->currency) }}</p>
                        <x-status-badge :status="$order->status" />
                    </div>
                </div>
            @empty
                <p class="p-5 text-sm text-ink-500">No orders yet.</p>
            @endforelse
        </section>
    </div>
</x-layouts.voter>
