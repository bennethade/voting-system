@php
    $socialLabels = ['instagram' => 'Instagram', 'x' => 'X (Twitter)', 'tiktok' => 'TikTok', 'youtube' => 'YouTube', 'facebook' => 'Facebook', 'linkedin' => 'LinkedIn'];
    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'Person',
        'name' => $nominee->name,
        'description' => $nominee->short_description,
        'image' => media_url($nominee->image),
        'url' => route('nominees.show', $nominee),
        'sameAs' => array_values($nominee->socialLinks()),
        'award' => "Nominee, {$category->name} — {$event->name}",
    ];
@endphp

<x-layouts.app :title="$nominee->name.' — '.$category->name" :description="$nominee->short_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $nominee->bio), 155) ?: 'Vote for '.$nominee->name.' in '.$category->name.'.'" :image="media_url($nominee->image)" type="profile" :json-ld="$jsonLd">

    <section class="hero-glow text-white">
        <div class="container-page grid gap-8 py-10 sm:py-14 md:grid-cols-12 md:items-center">
            <div class="md:col-span-5 lg:col-span-4">
                <div class="mx-auto aspect-[4/5] max-w-sm overflow-hidden rounded-3xl bg-ink-800 ring-1 ring-white/10">
                    @if ($nominee->image)
                        <img src="{{ media_url($nominee->image) }}" alt="{{ $nominee->name }}" class="size-full object-cover">
                    @else
                        <div class="grid size-full place-items-center font-display text-7xl text-gold-300">{{ initials($nominee->name) }}</div>
                    @endif
                </div>
            </div>
            <div class="md:col-span-7 lg:col-span-8">
                <nav class="text-sm text-ink-300" aria-label="Breadcrumb">
                    <a href="{{ route('voting.event', $event) }}" class="hover:text-white">{{ $event->name }}</a> /
                    <a href="{{ route('voting.category', [$event, $category]) }}" class="hover:text-white">{{ $category->name }}</a>
                </nav>
                <p class="eyebrow mt-5 text-gold-300">Nominee · {{ $category->name }}</p>
                <h1 class="mt-2 text-4xl font-semibold sm:text-6xl">{{ $nominee->name }}</h1>
                @if ($nominee->short_description)
                    <p class="mt-4 max-w-2xl text-lg text-ink-200">{{ $nominee->short_description }}</p>
                @endif

                @if ($showCounts)
                    <p class="mt-6 inline-flex items-baseline gap-2 rounded-2xl bg-white/5 px-5 py-3 ring-1 ring-white/10">
                        <span class="font-display text-3xl font-semibold text-gold-300">{{ number_format($nominee->total_votes) }}</span>
                        <span class="text-sm text-ink-200">votes so far</span>
                    </p>
                @endif

                <div class="mt-8 max-w-md">
                    @if (! $votingOpen)
                        <x-voting-status :event="$event" />
                    @else
                        <div class="grid gap-3 sm:grid-cols-2">
                            @if ($event->setting('free_voting_enabled'))
                                @auth
                                    @if ($freeVoteAvailable === false)
                                        <span class="btn btn-ghost-light btn-lg cursor-default opacity-70">Free vote used</span>
                                    @else
                                        <form method="POST" action="{{ route('votes.free', $nominee) }}" x-data="submitOnce" @submit="submit">
                                            @csrf
                                            <button class="btn btn-ghost-light btn-lg w-full" :disabled="submitting">Cast free vote</button>
                                        </form>
                                    @endif
                                @else
                                    <a href="{{ route('login', ['return' => request()->getRequestUri()]) }}" class="btn btn-ghost-light btn-lg">Sign in to vote free</a>
                                @endauth
                            @endif
                            @if ($event->setting('paid_voting_enabled'))
                                <button type="button" class="btn btn-gold btn-lg" x-data @click="$dispatch('open-paid-vote', { slug: @js($nominee->slug), name: @js($nominee->name) })">Buy votes</button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <div class="container-page mt-12 grid gap-10 lg:grid-cols-12">
        <article class="lg:col-span-8">
            <h2 class="text-2xl font-semibold">About {{ $nominee->name }}</h2>
            <div class="prose-content mt-4">
                @if ($nominee->bio)
                    {!! nl2br(e($nominee->bio)) !!}
                @else
                    <p class="text-ink-500">Biography coming soon.</p>
                @endif
            </div>
        </article>

        <aside class="space-y-6 lg:col-span-4">
            @if ($nominee->socialLinks() || $nominee->website)
                <div class="card p-5">
                    <h2 class="font-sans text-base font-semibold">Follow {{ $nominee->name }}</h2>
                    <ul class="mt-3 space-y-2 text-sm">
                        @foreach ($nominee->socialLinks() as $network => $url)
                            <li><a href="{{ $url }}" target="_blank" rel="noopener noreferrer nofollow" class="flex items-center justify-between rounded-xl px-3 py-2.5 hover:bg-ink-50"><span>{{ $socialLabels[$network] ?? ucfirst($network) }}</span><span aria-hidden="true">↗</span></a></li>
                        @endforeach
                        @if ($nominee->website)
                            <li><a href="{{ $nominee->website }}" target="_blank" rel="noopener noreferrer nofollow" class="flex items-center justify-between rounded-xl px-3 py-2.5 hover:bg-ink-50"><span>Website</span><span aria-hidden="true">↗</span></a></li>
                        @endif
                    </ul>
                </div>
            @endif

            <div class="card p-5" x-data="{ copied: false }">
                <h2 class="font-sans text-base font-semibold">Rally support</h2>
                <p class="mt-1 text-sm text-ink-500">Share this page so fans can vote.</p>
                <div class="mt-4 grid grid-cols-2 gap-2">
                    <a class="btn btn-outline btn-sm" target="_blank" rel="noopener" href="https://wa.me/?text={{ urlencode('Vote for '.$nominee->name.' — '.route('nominees.show', $nominee)) }}">WhatsApp</a>
                    <a class="btn btn-outline btn-sm" target="_blank" rel="noopener" href="https://twitter.com/intent/tweet?text={{ urlencode('Vote for '.$nominee->name) }}&url={{ urlencode(route('nominees.show', $nominee)) }}">Post on X</a>
                    <button type="button" class="btn btn-outline btn-sm col-span-2" @click="navigator.clipboard.writeText(@js(route('nominees.show', $nominee))); copied = true; setTimeout(() => copied = false, 2000)">
                        <span x-show="!copied">Copy link</span><span x-cloak x-show="copied">Link copied!</span>
                    </button>
                </div>
            </div>
        </aside>
    </div>

    @if ($votingOpen && $event->setting('paid_voting_enabled'))
        <x-paid-vote-modal :packages="$packages" :category="$category" :event="$event" />
    @endif
</x-layouts.app>
