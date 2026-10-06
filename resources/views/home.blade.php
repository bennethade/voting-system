<x-layouts.app :description="setting('seo_description')" :json-ld="$event ? [
    '@context' => 'https://schema.org',
    '@type' => 'Event',
    'name' => $event->name,
    'description' => $event->short_description,
    'startDate' => $event->event_date?->toIso8601String(),
    'eventStatus' => 'https://schema.org/EventScheduled',
    'location' => $event->venue ? ['@type' => 'Place', 'name' => $event->venue] : null,
    'image' => media_url($event->banner),
    'url' => route('events.show', $event),
] : null">

    {{-- Hero --}}
    <section class="hero-glow relative overflow-hidden text-white">
        <div class="container-page grid items-center gap-12 py-16 sm:py-24 lg:grid-cols-12 lg:py-28">
            <div class="lg:col-span-7">
                @if ($event)
                    <p class="inline-flex items-center gap-2 rounded-full border border-gold-400/30 bg-gold-400/10 px-3 py-1 text-xs font-semibold tracking-wide text-gold-200">
                        @if ($event->isVotingOpen())
                            <span class="relative flex size-2"><span class="absolute inline-flex size-full animate-ping rounded-full bg-gold-300 opacity-75"></span><span class="relative inline-flex size-2 rounded-full bg-gold-300"></span></span>
                            Voting is live · {{ $event->name }}
                        @else
                            {{ $event->name }}
                        @endif
                    </p>
                @endif
                <h1 class="mt-6 text-4xl leading-[1.05] font-semibold sm:text-6xl lg:text-7xl">{{ setting('hero_title') }}</h1>
                <p class="mt-6 max-w-xl text-lg text-ink-200">{{ setting('hero_subtitle') }}</p>
                <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('voting.index') }}" class="btn btn-gold btn-lg">Vote now</a>
                    <a href="{{ route('leaderboard') }}" class="btn btn-ghost-light btn-lg">See the leaderboard</a>
                </div>
                @if ($event?->voting_end_at && $event->isVotingOpen())
                    <p class="mt-6 text-sm text-ink-300">Voting closes {{ $event->voting_end_at->format('l, j F Y \a\t g:ia') }}</p>
                @endif
            </div>

            <div class="hidden lg:col-span-5 lg:block">
                <div class="relative mx-auto max-w-sm">
                    <div class="absolute -inset-6 rounded-[2.5rem] bg-gold-400/20 blur-3xl"></div>
                    <div class="relative grid grid-cols-2 gap-4">
                        @foreach ($featuredNominees->take(4) as $i => $nominee)
                            <a href="{{ route('nominees.show', $nominee) }}" @class(['group relative overflow-hidden rounded-3xl bg-ink-800 ring-1 ring-white/10', 'mt-10' => $i % 2 === 1])>
                                @if ($nominee->image)
                                    <img src="{{ media_url($nominee->image) }}" alt="{{ $nominee->name }}" class="aspect-[3/4] w-full object-cover opacity-90 transition group-hover:scale-105">
                                @else
                                    <div class="grid aspect-[3/4] place-items-center font-display text-4xl text-gold-300">{{ initials($nominee->name) }}</div>
                                @endif
                                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-ink-950/90 p-3 text-sm font-semibold">{{ $nominee->name }}</div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="container-page -mt-8 relative z-10">
        <div class="grid gap-4 sm:grid-cols-3">
            @foreach ([
                ['1', 'Pick a category', 'Browse every category and meet the nominees.'],
                ['2', 'Cast your free vote', 'Sign up, verify your email and vote free.'],
                ['3', 'Power up with packages', 'Buy extra votes securely with Paystack.'],
            ] as [$step, $title, $text])
                <div class="card flex items-start gap-4 p-5">
                    <span class="grid size-10 shrink-0 place-items-center rounded-full bg-gold-100 font-display text-lg font-semibold text-gold-800">{{ $step }}</span>
                    <div>
                        <h2 class="font-sans text-base font-semibold">{{ $title }}</h2>
                        <p class="mt-1 text-sm text-ink-500">{{ $text }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Categories --}}
    @if ($event && $categories->isNotEmpty())
        <section class="container-page mt-20">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="eyebrow">{{ $event->name }}</p>
                    <h2 class="mt-2 text-3xl font-semibold sm:text-4xl">Award categories</h2>
                </div>
                <a href="{{ route('voting.event', $event) }}" class="btn btn-outline">All categories</a>
            </div>
            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($categories->take(9) as $category)
                    <a href="{{ route('voting.category', [$event, $category]) }}" class="group card flex items-center justify-between gap-4 p-5 transition hover:border-gold-300 hover:shadow-md">
                        <div class="min-w-0">
                            <h3 class="truncate font-sans text-base font-semibold group-hover:text-gold-700">{{ $category->name }}</h3>
                            <p class="mt-0.5 text-sm text-ink-500">{{ $category->nominees_count }} {{ \Illuminate\Support\Str::plural('nominee', $category->nominees_count) }}</p>
                        </div>
                        <span class="grid size-10 shrink-0 place-items-center rounded-full bg-ink-50 text-ink-700 transition group-hover:bg-gold-400 group-hover:text-ink-950" aria-hidden="true">→</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Featured nominees --}}
    @if ($featuredNominees->isNotEmpty())
        <section class="container-page mt-20">
            <p class="eyebrow">Meet the nominees</p>
            <h2 class="mt-2 text-3xl font-semibold sm:text-4xl">Who are you backing?</h2>
            <div class="mt-8 grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-4">
                @foreach ($featuredNominees as $nominee)
                    <a href="{{ route('nominees.show', $nominee) }}" class="group">
                        <div class="aspect-[4/5] overflow-hidden rounded-2xl bg-ink-100">
                            @if ($nominee->image)
                                <img src="{{ media_url($nominee->image) }}" alt="{{ $nominee->name }}" loading="lazy" class="size-full object-cover transition duration-500 group-hover:scale-105">
                            @else
                                <div class="grid size-full place-items-center bg-gradient-to-br from-ink-800 to-ink-950 font-display text-4xl text-gold-300">{{ initials($nominee->name) }}</div>
                            @endif
                        </div>
                        <h3 class="mt-3 font-sans text-sm font-semibold sm:text-base">{{ $nominee->name }}</h3>
                        <p class="text-xs text-ink-500 sm:text-sm">{{ $nominee->category->name }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Packages --}}
    @if ($packages->isNotEmpty())
        <section class="mt-20 bg-ink-950 py-16 text-white">
            <div class="container-page">
                <div class="max-w-2xl">
                    <p class="eyebrow text-gold-300">Vote packages</p>
                    <h2 class="mt-2 text-3xl font-semibold sm:text-4xl">Give your favourite a boost</h2>
                    <p class="mt-3 text-ink-300">Every package is credited instantly once your payment is confirmed.</p>
                </div>
                <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    @foreach ($packages as $package)
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4 text-center">
                            <p class="font-display text-2xl font-semibold text-gold-300">{{ money($package->amount, $event->currency()) }}</p>
                            <p class="mt-1 text-sm text-ink-200">{{ number_format($package->votes) }} {{ \Illuminate\Support\Str::plural('vote', $package->votes) }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- About blurb --}}
    @if (setting('home_about'))
        <section class="container-page mt-20 grid gap-8 lg:grid-cols-2 lg:items-center">
            <div>
                <p class="eyebrow">About the awards</p>
                <h2 class="mt-2 text-3xl font-semibold sm:text-4xl">Celebrating the people behind the content</h2>
            </div>
            <div class="prose-content text-lg">{!! nl2br(e(setting('home_about'))) !!}</div>
        </section>
    @endif

    {{-- Gallery teaser --}}
    @if ($gallery->isNotEmpty())
        <section class="container-page mt-20">
            <div class="flex items-end justify-between gap-4">
                <h2 class="text-3xl font-semibold sm:text-4xl">Moments</h2>
                <a href="{{ route('gallery') }}" class="btn btn-outline">View gallery</a>
            </div>
            <div class="mt-8 grid grid-cols-2 gap-3 md:grid-cols-3">
                @foreach ($gallery as $item)
                    <img src="{{ media_url($item->file_path) }}" alt="{{ $item->title }}" loading="lazy" class="aspect-square w-full rounded-2xl object-cover">
                @endforeach
            </div>
        </section>
    @endif

    {{-- CTA --}}
    <section class="container-page mt-20">
        <div class="hero-glow overflow-hidden rounded-3xl px-6 py-12 text-center text-white sm:px-12">
            <h2 class="text-3xl font-semibold sm:text-4xl">Be in the room on awards night</h2>
            <p class="mx-auto mt-3 max-w-xl text-ink-200">Reserve a table for you and your team and celebrate the winners live.</p>
            <div class="mt-7 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ route('bookings.index') }}" class="btn btn-gold btn-lg">Book a table</a>
                <a href="{{ route('donate') }}" class="btn btn-ghost-light btn-lg">Support the awards</a>
            </div>
        </div>
    </section>
</x-layouts.app>
