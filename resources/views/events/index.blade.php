<x-layouts.app title="Events" description="Past and upcoming award events.">
    <x-page-header title="Award events" subtitle="Every edition, past and upcoming." />

    <div class="container-page mt-10">
        @if ($events->isEmpty())
            <x-empty-state title="No events yet" />
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($events as $event)
                    <a href="{{ route('events.show', $event) }}" class="group card overflow-hidden transition hover:shadow-lg">
                        <div class="aspect-[16/9] overflow-hidden bg-ink-900">
                            @if ($event->banner)
                                <img src="{{ media_url($event->banner) }}" alt="" loading="lazy" class="size-full object-cover transition group-hover:scale-105">
                            @else
                                <div class="hero-glow grid size-full place-items-center p-6 text-center font-display text-2xl text-white">{{ $event->name }}</div>
                            @endif
                        </div>
                        <div class="p-5">
                            <div class="flex items-center justify-between gap-2">
                                <x-status-badge :status="$event->status" />
                                @if ($event->event_date)<span class="text-xs text-ink-500">{{ $event->event_date->format('j M Y') }}</span>@endif
                            </div>
                            <h2 class="mt-3 font-sans text-lg font-semibold group-hover:text-gold-700">{{ $event->name }}</h2>
                            @if ($event->short_description)<p class="mt-1 line-clamp-2 text-sm text-ink-500">{{ $event->short_description }}</p>@endif
                            <p class="mt-3 text-xs text-ink-400">{{ $event->categories_count }} categories</p>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="mt-8">{{ $events->links() }}</div>
        @endif
    </div>
</x-layouts.app>
