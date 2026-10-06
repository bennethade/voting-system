@props(['column', 'label'])

@php
    $current = request('sort');
    $direction = request('direction', 'desc');
    $isActive = $current === $column;
    $next = $isActive && $direction === 'asc' ? 'desc' : 'asc';
@endphp

<a href="{{ request()->fullUrlWithQuery(['sort' => $column, 'direction' => $next, 'page' => null]) }}" class="inline-flex items-center gap-1 hover:text-ink-900">
    {{ $label }}
    @if ($isActive)
        <span aria-hidden="true">{{ $direction === 'asc' ? '↑' : '↓' }}</span>
    @endif
</a>
