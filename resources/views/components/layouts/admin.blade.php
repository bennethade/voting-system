@props(['title'])

@php
    $user = auth()->user();
    $groups = [
        'Overview' => [
            ['admin.dashboard', 'Dashboard', 'dashboard.view', 'admin.dashboard', 'M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10'],
            ['admin.leaderboard', 'Leaderboard', 'leaderboard.view', 'admin.leaderboard', 'M8 21h8M12 17v4M7 4h10v5a5 5 0 01-10 0V4zM17 5h3a3 3 0 01-3 4M7 5H4a3 3 0 003 4'],
            ['admin.reports.index', 'Reports', null, 'admin.reports.*', 'M9 17v-6m4 6V7m4 10v-3M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z'],
        ],
        'Awards' => [
            ['admin.events.index', 'Award events', 'events.manage', 'admin.events.*', 'M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z'],
            ['admin.categories.index', 'Categories', 'categories.manage', 'admin.categories.*', 'M4 6h16M4 12h16M4 18h7'],
            ['admin.nominees.index', 'Nominees', 'nominees.manage', 'admin.nominees.*', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
            ['admin.voting-packages.index', 'Voting packages', 'packages.manage', 'admin.voting-packages.*', 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
        ],
        'Voting' => [
            ['admin.votes.index', 'Votes', 'votes.view', 'admin.votes.*', 'M9 12l2 2 4-4m5.6-4.02A11.96 11.96 0 0112 2.94a11.96 11.96 0 01-8.6 3.04A12 12 0 003 9c0 5.6 3.82 10.3 9 11.62 5.18-1.33 9-6.03 9-11.62 0-1.04-.13-2.05-.38-3.02z'],
            ['admin.fraud.index', 'Fraud monitoring', 'fraud.manage', 'admin.fraud.*', 'M12 9v3.75m-9.3 3.38c-.87 1.5.22 3.37 1.95 3.37h14.7c1.73 0 2.82-1.87 1.95-3.37L13.95 3.38c-.87-1.5-3.03-1.5-3.9 0L2.7 16.13zM12 15.75h.01'],
            ['admin.voters.index', 'Voters', 'voters.manage', 'admin.voters.*', 'M17 20h5v-2a3 3 0 00-5.36-1.86M17 20H7m10 0v-2c0-.66-.13-1.28-.36-1.86M7 20H2v-2a3 3 0 015.36-1.86M7 20v-2c0-.66.13-1.28.36-1.86m0 0a5 5 0 019.28 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
        ],
        'Finance' => [
            ['admin.orders.index', 'Vote orders', 'orders.view', 'admin.orders.*', 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
            ['admin.payments.index', 'Payments', 'payments.view', 'admin.payments.*', 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
            ['admin.donations.index', 'Donations', 'donations.view', 'admin.donations.*', 'M4.3 6.3a4.5 4.5 0 000 6.4L12 20.4l7.7-7.7a4.5 4.5 0 00-6.4-6.4L12 7.6l-1.3-1.3a4.5 4.5 0 00-6.4 0z'],
            ['admin.bookings.index', 'Event bookings', 'bookings.view', 'admin.bookings.*', 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z'],
            ['admin.event-packages.index', 'Table packages', 'booking_packages.manage', 'admin.event-packages.*', 'M3 10h18M5 10V20m14-10v10M8 6h8l2 4H6l2-4z'],
        ],
        'Content' => [
            ['admin.gallery.index', 'Gallery', 'gallery.manage', 'admin.gallery.*', 'M4 16l4.6-4.6a2 2 0 012.8 0L16 16m-2-2l1.6-1.6a2 2 0 012.8 0L20 14M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
            ['admin.pages.index', 'Pages', 'content.manage', 'admin.pages.*', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.6a1 1 0 01.7.3l5.4 5.4a1 1 0 01.3.7V19a2 2 0 01-2 2z'],
            ['admin.faqs.index', 'FAQs', 'content.manage', 'admin.faqs.*', 'M8.2 9a4 4 0 017.6 1c0 2-3 3-3 3m.2 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['admin.messages.index', 'Messages', 'contact.manage', 'admin.messages.*', 'M3 8l7.9 5.26a2 2 0 002.2 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
        ],
        'System' => [
            ['admin.administrators.index', 'Administrators', 'admins.manage', 'admin.administrators.*', 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'],
            ['admin.audit-logs.index', 'Audit log', 'audit.view', 'admin.audit-logs.*', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
            ['admin.settings.edit', 'Settings', 'settings.manage', 'admin.settings.*', 'M10.3 4.3c.4-1.7 2.9-1.7 3.4 0a1.7 1.7 0 002.6 1.1c1.5-.9 3.3.8 2.4 2.4a1.7 1.7 0 001 2.5c1.8.4 1.8 2.9 0 3.4a1.7 1.7 0 00-1 2.6c.9 1.5-.9 3.3-2.4 2.4a1.7 1.7 0 00-2.6 1c-.4 1.8-2.9 1.8-3.4 0a1.7 1.7 0 00-2.5-1c-1.6.9-3.3-.9-2.4-2.4a1.7 1.7 0 00-1.1-2.6c-1.7-.4-1.7-2.9 0-3.4a1.7 1.7 0 001.1-2.5c-.9-1.6.8-3.3 2.4-2.4 1 .6 2.3.1 2.5-1.1zM15 12a3 3 0 11-6 0 3 3 0 016 0z'],
        ],
    ];
    $canReports = $user->can('reports.voting') || $user->can('reports.financial') || $user->can('reports.voters');
@endphp

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} · Admin · {{ setting('site_name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="h-full bg-ink-50/60" x-data="{ sidebar: false }">
    {{-- Mobile overlay --}}
    <div x-cloak x-show="sidebar" x-transition.opacity class="fixed inset-0 z-40 bg-ink-950/60 lg:hidden" @click="sidebar = false"></div>

    <aside class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col bg-ink-950 text-ink-200 transition lg:translate-x-0"
           :class="sidebar && 'translate-x-0'">
        <div class="flex h-16 items-center justify-between px-5">
            <x-logo :href="route('admin.dashboard')" class="text-base" />
            <button class="text-2xl lg:hidden" @click="sidebar = false" aria-label="Close menu">&times;</button>
        </div>
        <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4" aria-label="Admin">
            @foreach ($groups as $group => $links)
                @php
                    $visible = array_filter($links, fn ($l) => $l[2] === null ? $canReports : $user->can($l[2]));
                @endphp
                @if ($visible)
                    <div>
                        <p class="px-3 text-[11px] font-semibold tracking-widest text-ink-500 uppercase">{{ $group }}</p>
                        <ul class="mt-2 space-y-0.5">
                            @foreach ($visible as [$route, $label, $perm, $pattern, $icon])
                                <li>
                                    <a href="{{ route($route) }}" @class([
                                        'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                                        'bg-white/10 text-white' => request()->routeIs($pattern),
                                        'hover:bg-white/5 hover:text-white' => ! request()->routeIs($pattern),
                                    ])>
                                        <svg class="size-5 shrink-0 {{ request()->routeIs($pattern) ? 'text-gold-300' : 'text-ink-400' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                                        {{ $label }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endforeach
        </nav>
        <div class="border-t border-white/10 p-4">
            <div class="flex items-center gap-3">
                <span class="grid size-9 place-items-center rounded-full bg-gold-400 text-xs font-bold text-ink-950">{{ initials($user->name) }}</span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-white">{{ $user->name }}</p>
                    <p class="truncate text-xs text-ink-400">{{ $user->role->label() }}</p>
                </div>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="rounded-lg p-2 hover:bg-white/10" title="Sign out" aria-label="Sign out">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div class="lg:pl-72">
        <header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-ink-100 bg-white/90 px-4 backdrop-blur sm:px-6">
            <button class="grid size-10 place-items-center rounded-lg hover:bg-ink-50 lg:hidden" @click="sidebar = true" aria-label="Open menu">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
            <h1 class="truncate font-sans text-lg font-semibold">{{ $title }}</h1>
            <a href="{{ route('home') }}" target="_blank" class="ml-auto text-sm font-medium text-ink-500 hover:text-ink-900">View site ↗</a>
        </header>

        <x-flash />

        <main class="px-4 py-6 sm:px-6 lg:px-8">
            @if ($errors->any() && ! isset($hideErrorSummary))
                <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
                    Please fix the highlighted fields below.
                </div>
            @endif
            {{ $slot }}
        </main>
    </div>

    @vite('resources/js/admin.js')
    @stack('scripts')
</body>
</html>
