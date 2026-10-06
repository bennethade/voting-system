@props(['href' => null, 'dark' => false])

<a href="{{ $href ?? route('home') }}" {{ $attributes->class(['flex items-center gap-2.5 font-display text-lg font-semibold', 'text-white' => ! $dark, 'text-ink-900' => $dark]) }}>
    {{-- Original mark: a stylised laurel around a star --}}
    <svg class="size-8 shrink-0" viewBox="0 0 32 32" fill="none" aria-hidden="true">
        <circle cx="16" cy="16" r="15" class="fill-gold-400"/>
        <path d="M16 8.5l2.06 4.6 5 .47-3.78 3.3 1.12 4.9L16 19.2l-4.4 2.57 1.12-4.9-3.78-3.3 5-.47L16 8.5z" class="fill-ink-950"/>
        <path d="M7.5 18.5c1 3.6 4.4 6 8.5 6s7.5-2.4 8.5-6" stroke="currentColor" class="text-ink-950" stroke-width="1.6" stroke-linecap="round"/>
    </svg>
    <span class="truncate">{{ setting('site_name') }}</span>
</a>
