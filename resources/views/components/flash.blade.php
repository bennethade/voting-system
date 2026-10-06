@php
    $messages = array_filter([
        'success' => session('success'),
        'error' => session('error'),
        'status' => session('status'),
    ]);
@endphp

@if ($messages)
    <div class="pointer-events-none fixed inset-x-0 top-20 z-50 flex flex-col items-center gap-2 px-4" role="status" aria-live="polite">
        @foreach ($messages as $type => $message)
            <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 7000)" x-show="show" x-transition.opacity.duration.300ms
                 @class([
                     'pointer-events-auto flex w-full max-w-lg items-start gap-3 rounded-2xl px-4 py-3 text-sm shadow-xl ring-1',
                     'bg-emerald-50 text-emerald-900 ring-emerald-200' => $type !== 'error',
                     'bg-rose-50 text-rose-900 ring-rose-200' => $type === 'error',
                 ])>
                @if ($type === 'error')
                    <svg class="mt-0.5 size-5 shrink-0 text-rose-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                @else
                    <svg class="mt-0.5 size-5 shrink-0 text-emerald-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></svg>
                @endif
                <p class="flex-1">{{ $message }}</p>
                <button type="button" @click="show = false" class="text-current/60 hover:text-current" aria-label="Dismiss">&times;</button>
            </div>
        @endforeach
    </div>
@endif
