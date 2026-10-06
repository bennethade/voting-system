@props(['event'])

@php($reason = $event->votingClosedReason())

@if ($reason)
    <div {{ $attributes->class('flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900') }}>
        <svg class="mt-0.5 size-5 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.49 2.5a1.75 1.75 0 013.02 0l6.25 10.83A1.75 1.75 0 0116.25 16H3.75a1.75 1.75 0 01-1.51-2.67L8.49 2.5zM10 6a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 7.5a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
        <p>{{ $reason }} @if ($event->voting_start_at && now()->lt($event->voting_start_at)) Voting opens {{ $event->voting_start_at->format('j M Y, g:ia') }}. @endif</p>
    </div>
@elseif ($event->voting_end_at)
    <div {{ $attributes->class('flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900') }}>
        <span class="relative flex size-2.5"><span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex size-2.5 rounded-full bg-emerald-500"></span></span>
        <p>Voting is open until <strong>{{ $event->voting_end_at->format('j M Y, g:ia') }}</strong> ({{ $event->voting_end_at->diffForHumans() }}).</p>
    </div>
@endif
