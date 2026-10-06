@props([
    'nominee',
    'category',
    'event',
    'votingOpen' => false,
    'showCounts' => false,
    'freeVoteAvailable' => null,
])

@php
    $freeEnabled = $event->setting('free_voting_enabled');
    $paidEnabled = $event->setting('paid_voting_enabled');
@endphp

<article class="group card flex flex-col overflow-hidden transition hover:-translate-y-0.5 hover:shadow-lg">
    <a href="{{ route('nominees.show', $nominee) }}" class="relative block aspect-[4/5] overflow-hidden bg-ink-100">
        @if ($nominee->image)
            <img src="{{ media_url($nominee->image) }}" alt="{{ $nominee->name }}" loading="lazy" decoding="async" class="size-full object-cover transition duration-500 group-hover:scale-105">
        @else
            <div class="grid size-full place-items-center bg-gradient-to-br from-ink-800 to-ink-950 font-display text-5xl text-gold-300">{{ initials($nominee->name) }}</div>
        @endif
        @if ($showCounts)
            <span class="absolute top-3 right-3 rounded-full bg-ink-950/80 px-3 py-1 text-xs font-semibold text-gold-300 backdrop-blur">{{ number_format($nominee->total_votes) }} votes</span>
        @endif
    </a>

    <div class="flex flex-1 flex-col p-4 sm:p-5">
        <p class="eyebrow">{{ $category->name }}</p>
        <h3 class="mt-1 text-xl font-semibold">
            <a href="{{ route('nominees.show', $nominee) }}" class="hover:text-gold-700">{{ $nominee->name }}</a>
        </h3>
        @if ($nominee->short_description)
            <p class="mt-2 line-clamp-2 text-sm text-ink-500">{{ $nominee->short_description }}</p>
        @endif

        <div class="mt-auto grid grid-cols-2 gap-2 pt-5">
            @if (! $votingOpen)
                <span class="btn btn-outline col-span-2 opacity-60">Voting closed</span>
            @else
                @if ($freeEnabled)
                    @auth
                        @if ($freeVoteAvailable === false)
                            <span class="btn btn-outline cursor-default opacity-60" title="You have used your free vote">Free vote used</span>
                        @else
                            <form method="POST" action="{{ route('votes.free', $nominee) }}" x-data="submitOnce" @submit="submit">
                                @csrf
                                <button type="submit" class="btn btn-outline w-full" :disabled="submitting">
                                    <span x-show="!submitting">Free vote</span>
                                    <span x-cloak x-show="submitting">Voting…</span>
                                </button>
                            </form>
                        @endif
                    @else
                        <a href="{{ route('login', ['return' => request()->getRequestUri()]) }}" class="btn btn-outline">Free vote</a>
                    @endauth
                @endif

                @if ($paidEnabled)
                    <button type="button" x-data
                            @click="$dispatch('open-paid-vote', { slug: @js($nominee->slug), name: @js($nominee->name) })"
                            @class(['btn btn-gold', 'col-span-2' => ! $freeEnabled])>
                        Buy votes
                    </button>
                @endif
            @endif
        </div>
    </div>
</article>
