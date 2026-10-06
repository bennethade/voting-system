@props(['status'])

@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $label = $status instanceof \BackedEnum && method_exists($status, 'label') ? $status->label() : ucfirst(str_replace('_', ' ', $value));
    $classes = match ($value) {
        'paid', 'success', 'active', 'valid', 'published', 'voting', 'approved', 'replied', 'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'pending', 'upcoming', 'reviewing', 'new', 'paused', 'open' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
        'failed', 'cancelled', 'invalidated', 'suspended', 'rejected' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
        'refunded', 'abandoned', 'closed', 'archived', 'inactive', 'draft', 'read', 'hidden' => 'bg-ink-50 text-ink-600 ring-ink-500/20',
        default => 'bg-ink-50 text-ink-600 ring-ink-500/20',
    };
@endphp

<span {{ $attributes->class(['badge ring-1 ring-inset', $classes]) }}>{{ $label }}</span>
