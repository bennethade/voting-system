@props(['title'])

@php
    $links = [
        ['voter.dashboard', 'Overview'],
        ['voter.votes', 'My votes'],
        ['voter.orders', 'Orders'],
        ['voter.payments', 'Payments'],
        ['voter.profile', 'Profile'],
    ];
@endphp

<x-layouts.app :title="$title" :noindex="true">
    <section class="hero-glow text-white">
        <div class="container-page pt-10 pb-6">
            <p class="eyebrow text-gold-300">My account</p>
            <h1 class="mt-2 text-3xl font-semibold sm:text-4xl">{{ $title }}</h1>
            <nav class="-mx-4 mt-6 flex gap-1 overflow-x-auto px-4 pb-1" aria-label="Account">
                @foreach ($links as [$route, $label])
                    <a href="{{ route($route) }}" @class(['shrink-0 rounded-full px-4 py-2 text-sm font-medium', 'bg-white text-ink-900' => request()->routeIs($route), 'text-ink-200 hover:bg-white/10' => ! request()->routeIs($route)])>{{ $label }}</a>
                @endforeach
            </nav>
        </div>
    </section>

    @if (! auth()->user()->hasVerifiedEmail())
        <div class="container-page mt-6">
            <div class="flex flex-col gap-3 rounded-2xl border border-gold-200 bg-gold-50 p-4 text-sm text-gold-900 sm:flex-row sm:items-center sm:justify-between">
                <p>Your email address isn't verified yet. Verify it to unlock free voting.</p>
                <a href="{{ route('verification.notice') }}" class="btn btn-gold btn-sm">Verify now</a>
            </div>
        </div>
    @endif

    <div class="container-page mt-8">
        {{ $slot }}
    </div>
</x-layouts.app>
