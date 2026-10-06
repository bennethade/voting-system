@props(['label', 'value', 'hint' => null, 'href' => null])

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class('card block p-4 transition hover:border-gold-300 hover:shadow-md sm:p-5') }}>
@else
    <div {{ $attributes->class('card p-4 sm:p-5') }}>
@endif
        <p class="text-xs font-medium text-ink-500 sm:text-sm">{{ $label }}</p>
        <p class="mt-1.5 font-display text-2xl font-semibold text-ink-900 tabular-nums sm:text-3xl">{{ $value }}</p>
        @if ($hint)<p class="mt-1 text-xs text-ink-400">{{ $hint }}</p>@endif
@if ($href)
    </a>
@else
    </div>
@endif
