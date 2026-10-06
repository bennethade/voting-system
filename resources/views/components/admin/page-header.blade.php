@props(['title', 'subtitle' => null])

<div {{ $attributes->class('mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between') }}>
    <div>
        <h2 class="font-sans text-2xl font-semibold text-ink-900">{{ $title }}</h2>
        @if ($subtitle)<p class="mt-1 text-sm text-ink-500">{{ $subtitle }}</p>@endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap gap-2">{{ $actions }}</div>
    @endisset
</div>
