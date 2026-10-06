@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'canonical' => null,
    'noindex' => false,
    'jsonLd' => null,
    'type' => 'website',
])

@php
    $siteName = setting('site_name');
    $fullTitle = $title ? "{$title} | {$siteName}" : "{$siteName} — ".setting('site_tagline');
    $metaDescription = \Illuminate\Support\Str::limit(strip_tags($description ?: setting('seo_description')), 160);
    $canonicalUrl = $canonical ?: url()->current();
    $ogImage = $image ?: asset('images/og-default.png');
    $nav = [
        ['label' => 'Vote', 'route' => 'voting.index', 'active' => 'voting.*'],
        ['label' => 'Leaderboard', 'route' => 'leaderboard', 'active' => 'leaderboard'],
        ['label' => 'Events', 'route' => 'events.index', 'active' => 'events.*'],
        ['label' => 'Gallery', 'route' => 'gallery', 'active' => 'gallery'],
        ['label' => 'Book a Table', 'route' => 'bookings.index', 'active' => 'bookings.*'],
        ['label' => 'Donate', 'route' => 'donate', 'active' => 'donate*'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0a0c1c">

    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    @if ($noindex)
        <meta name="robots" content="noindex, nofollow">
    @endif

    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:type" content="{{ $type }}">
    <meta property="og:title" content="{{ $title ?: $siteName }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?: $siteName }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
    @if (setting('social_x'))
        <meta name="twitter:site" content="{{ '@'.ltrim(basename(parse_url(setting('social_x'), PHP_URL_PATH) ?? ''), '@') }}">
    @endif

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

    @if ($jsonLd)
        <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    @endif

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="flex min-h-screen flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2">Skip to content</a>

    <header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-white/10 bg-ink-950/95 text-white backdrop-blur supports-[backdrop-filter]:bg-ink-950/80">
        <div class="container-page flex h-16 items-center justify-between gap-4">
            <x-logo />

            <nav class="hidden items-center gap-1 lg:flex" aria-label="Main">
                @foreach ($nav as $item)
                    <a href="{{ route($item['route']) }}"
                       @class([
                           'rounded-full px-3.5 py-2 text-sm font-medium transition',
                           'bg-white/10 text-gold-300' => request()->routeIs($item['active']),
                           'text-ink-100 hover:text-white hover:bg-white/5' => ! request()->routeIs($item['active']),
                       ])>{{ $item['label'] }}</a>
                @endforeach
            </nav>

            <div class="hidden items-center gap-2 lg:flex">
                @auth
                    <div x-data="{ menu: false }" class="relative">
                        <button type="button" @click="menu = !menu" @click.outside="menu = false" class="flex items-center gap-2 rounded-full border border-white/15 py-1 pr-3 pl-1 text-sm hover:bg-white/5" :aria-expanded="menu">
                            <span class="grid size-8 place-items-center rounded-full bg-gold-400 text-xs font-bold text-ink-950">{{ initials(auth()->user()->name) }}</span>
                            <span class="max-w-32 truncate">{{ auth()->user()->name }}</span>
                        </button>
                        <div x-cloak x-show="menu" x-transition class="absolute right-0 mt-2 w-56 overflow-hidden rounded-xl bg-white py-1 text-sm text-ink-800 shadow-xl ring-1 ring-ink-100">
                            @if (auth()->user()->isStaff())
                                <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2.5 hover:bg-ink-50">Admin panel</a>
                            @endif
                            <a href="{{ route('voter.dashboard') }}" class="block px-4 py-2.5 hover:bg-ink-50">My dashboard</a>
                            <a href="{{ route('voter.votes') }}" class="block px-4 py-2.5 hover:bg-ink-50">My votes</a>
                            <a href="{{ route('voter.profile') }}" class="block px-4 py-2.5 hover:bg-ink-50">Profile</a>
                            <form method="POST" action="{{ route('logout') }}" class="border-t border-ink-100">
                                @csrf
                                <button class="block w-full px-4 py-2.5 text-left hover:bg-ink-50">Sign out</button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="rounded-full px-4 py-2 text-sm font-medium text-ink-100 hover:text-white">Sign in</a>
                    <a href="{{ route('register') }}" class="btn btn-gold btn-sm">Create account</a>
                @endauth
            </div>

            <button type="button" class="grid size-11 place-items-center rounded-full hover:bg-white/10 lg:hidden" @click="open = !open" :aria-expanded="open" aria-label="Toggle menu">
                <svg x-show="!open" class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                <svg x-cloak x-show="open" class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>

        {{-- Mobile menu --}}
        <div x-cloak x-show="open" x-collapse class="border-t border-white/10 lg:hidden">
            <nav class="container-page grid gap-1 py-4" aria-label="Mobile">
                @foreach ($nav as $item)
                    <a href="{{ route($item['route']) }}" @class(['rounded-xl px-4 py-3 text-base font-medium', 'bg-white/10 text-gold-300' => request()->routeIs($item['active']), 'text-ink-100' => ! request()->routeIs($item['active'])])>{{ $item['label'] }}</a>
                @endforeach
                <a href="{{ route('contact') }}" class="rounded-xl px-4 py-3 text-base font-medium text-ink-100">Contact</a>
                <div class="mt-3 grid grid-cols-2 gap-2 border-t border-white/10 pt-4">
                    @auth
                        <a href="{{ route('voter.dashboard') }}" class="btn btn-ghost-light">Dashboard</a>
                        <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-ghost-light w-full">Sign out</button></form>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-ghost-light">Sign in</a>
                        <a href="{{ route('register') }}" class="btn btn-gold">Register</a>
                    @endauth
                </div>
            </nav>
        </div>
    </header>

    <x-flash />

    <main id="main" class="flex-1">
        {{ $slot }}
    </main>

    <footer class="mt-20 bg-ink-950 text-ink-200">
        <div class="container-page grid gap-10 py-14 md:grid-cols-4">
            <div class="md:col-span-2">
                <x-logo />
                <p class="mt-4 max-w-md text-sm leading-relaxed text-ink-300">{{ setting('footer_text') ?: setting('site_tagline') }}</p>
                <div class="mt-5 flex gap-3">
                    @foreach (['instagram' => 'Instagram', 'x' => 'X', 'tiktok' => 'TikTok', 'youtube' => 'YouTube', 'facebook' => 'Facebook'] as $key => $label)
                        @if (setting("social_{$key}"))
                            <a href="{{ setting("social_{$key}") }}" target="_blank" rel="noopener noreferrer" class="rounded-full border border-white/15 px-3 py-1.5 text-xs hover:border-gold-400 hover:text-gold-300">{{ $label }}</a>
                        @endif
                    @endforeach
                </div>
            </div>
            <div>
                <h2 class="font-sans text-sm font-semibold text-white">Explore</h2>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="{{ route('about') }}" class="hover:text-gold-300">About</a></li>
                    <li><a href="{{ route('voting.index') }}" class="hover:text-gold-300">Vote now</a></li>
                    <li><a href="{{ route('leaderboard') }}" class="hover:text-gold-300">Leaderboard</a></li>
                    <li><a href="{{ route('faq') }}" class="hover:text-gold-300">FAQ</a></li>
                    <li><a href="{{ route('contact') }}" class="hover:text-gold-300">Contact</a></li>
                </ul>
            </div>
            <div>
                <h2 class="font-sans text-sm font-semibold text-white">Legal</h2>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="{{ route('terms') }}" class="hover:text-gold-300">Terms of use</a></li>
                    <li><a href="{{ route('privacy') }}" class="hover:text-gold-300">Privacy policy</a></li>
                    <li><a href="{{ route('refund-policy') }}" class="hover:text-gold-300">Refund policy</a></li>
                </ul>
                @if (setting('contact_email'))
                    <p class="mt-6 text-sm"><a href="mailto:{{ setting('contact_email') }}" class="hover:text-gold-300">{{ setting('contact_email') }}</a></p>
                @endif
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="container-page flex flex-col gap-2 py-6 text-xs text-ink-400 sm:flex-row sm:justify-between">
                <p>&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
                <p>Payments securely processed by Paystack.</p>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
