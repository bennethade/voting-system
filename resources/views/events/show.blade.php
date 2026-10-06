<x-layouts.app :title="$event->name" :description="$event->short_description" :image="media_url($event->banner)" :json-ld="[
    '@context' => 'https://schema.org',
    '@type' => 'Event',
    'name' => $event->name,
    'description' => $event->short_description,
    'startDate' => $event->event_date?->toIso8601String(),
    'location' => $event->venue ? ['@type' => 'Place', 'name' => $event->venue] : null,
    'image' => media_url($event->banner),
]">
    <section class="hero-glow relative overflow-hidden text-white">
        @if ($event->banner)
            <img src="{{ media_url($event->banner) }}" alt="" class="absolute inset-0 size-full object-cover opacity-25">
        @endif
        <div class="container-page relative py-16 sm:py-24">
            <x-status-badge :status="$event->status" />
            <h1 class="mt-4 max-w-3xl text-4xl font-semibold sm:text-6xl">{{ $event->name }}</h1>
            @if ($event->short_description)<p class="mt-4 max-w-2xl text-lg text-ink-200">{{ $event->short_description }}</p>@endif
            <dl class="mt-8 flex flex-wrap gap-x-10 gap-y-4 text-sm">
                @if ($event->event_date)<div><dt class="text-ink-300">Awards night</dt><dd class="mt-0.5 font-semibold">{{ $event->event_date->format('l, j F Y · g:ia') }}</dd></div>@endif
                @if ($event->venue)<div><dt class="text-ink-300">Venue</dt><dd class="mt-0.5 font-semibold">{{ $event->venue }}</dd></div>@endif
                @if ($event->voting_end_at)<div><dt class="text-ink-300">Voting closes</dt><dd class="mt-0.5 font-semibold">{{ $event->voting_end_at->format('j M Y · g:ia') }}</dd></div>@endif
            </dl>
            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('voting.event', $event) }}" class="btn btn-gold btn-lg">{{ $event->isVotingOpen() ? 'Vote now' : 'View nominees' }}</a>
                @if ($event->eventPackages->isNotEmpty())
                    <a href="{{ route('bookings.index', ['event' => $event->slug]) }}" class="btn btn-ghost-light btn-lg">Book a table</a>
                @endif
            </div>
        </div>
    </section>

    <div class="container-page mt-12 grid gap-12 lg:grid-cols-3">
        <div class="lg:col-span-2">
            @if ($event->description)
                <h2 class="text-2xl font-semibold">About this event</h2>
                <div class="prose-content mt-4">{!! nl2br(e($event->description)) !!}</div>
            @endif

            <h2 class="mt-12 text-2xl font-semibold">Categories</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @forelse ($event->activeCategories as $category)
                    <a href="{{ route('voting.category', [$event, $category]) }}" class="card flex items-center justify-between p-4 hover:border-gold-300">
                        <span class="font-medium">{{ $category->name }}</span>
                        <span class="text-xs text-ink-500">{{ $category->nominees_count }} nominees</span>
                    </a>
                @empty
                    <p class="text-ink-500">Categories will be announced soon.</p>
                @endforelse
            </div>
        </div>
        <aside>
            <div class="card p-5">
                <h2 class="font-sans text-base font-semibold">Results</h2>
                <p class="mt-1 text-sm text-ink-500">
                    {{ $event->resultsArePublic() ? 'Live results are available.' : 'Results are not public yet.' }}
                </p>
                <a href="{{ route('leaderboard', ['event' => $event->slug]) }}" class="btn btn-outline mt-4 w-full">Leaderboard</a>
            </div>
        </aside>
    </div>
</x-layouts.app>
