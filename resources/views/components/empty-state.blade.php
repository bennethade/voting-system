@props(['title' => 'Nothing here yet', 'message' => null])

<div {{ $attributes->class('rounded-2xl border border-dashed border-ink-200 bg-white/60 px-6 py-14 text-center') }}>
    <svg class="mx-auto size-10 text-ink-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.5a.56.56 0 011.04 0l2.13 5.11a.56.56 0 00.47.35l5.52.44c.5.04.7.66.32.99l-4.2 3.6a.56.56 0 00-.18.56l1.28 5.38a.56.56 0 01-.84.61l-4.73-2.89a.56.56 0 00-.59 0l-4.72 2.89a.56.56 0 01-.84-.61l1.28-5.38a.56.56 0 00-.18-.56l-4.2-3.6a.56.56 0 01.32-.99l5.52-.44a.56.56 0 00.47-.35L11.48 3.5z"/></svg>
    <h3 class="mt-3 font-sans text-base font-semibold text-ink-900">{{ $title }}</h3>
    @if ($message)
        <p class="mx-auto mt-1 max-w-sm text-sm text-ink-500">{{ $message }}</p>
    @endif
    @if (! $slot->isEmpty())
        <div class="mt-5">{{ $slot }}</div>
    @endif
</div>
