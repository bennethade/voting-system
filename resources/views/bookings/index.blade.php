<x-layouts.app title="Book a table" description="Reserve a table or seats at the awards night.">
    <x-page-header :eyebrow="$event?->name" title="Book a table" :subtitle="$event?->event_date ? $event->event_date->format('l, j F Y · g:ia').($event->venue ? ' · '.$event->venue : '') : 'Reserve your place at the awards night.'">
        @if ($events->count() > 1)
            <div class="mt-6 flex flex-wrap gap-2">
                @foreach ($events as $e)
                    <a href="{{ route('bookings.index', ['event' => $e->slug]) }}" @class(['rounded-full px-4 py-2 text-sm font-medium', 'bg-gold-400 text-ink-950' => $event?->id === $e->id, 'border border-white/20 text-white' => $event?->id !== $e->id])>{{ $e->name }}</a>
                @endforeach
            </div>
        @endif
    </x-page-header>

    <div class="container-page mt-10">
        @if ($packages->isEmpty())
            <x-empty-state title="Bookings aren't open yet" message="Table and seat packages will be announced soon." />
        @else
            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($packages as $i => $package)
                    <div @class(['card flex flex-col p-6', 'ring-2 ring-gold-400' => $i === 0])>
                        <div class="flex items-start justify-between gap-3">
                            <h2 class="text-2xl font-semibold">{{ $package->name }}</h2>
                            @if ($package->remaining() === 0)
                                <span class="badge bg-ink-100 text-ink-600">Sold out</span>
                            @elseif ($package->remaining() <= 3)
                                <span class="badge bg-rose-50 text-rose-700">{{ $package->remaining() }} left</span>
                            @endif
                        </div>
                        <p class="mt-3"><span class="font-display text-4xl font-semibold">{{ money($package->price, $event->currency()) }}</span></p>
                        <p class="mt-1 text-sm text-ink-500">{{ $package->seats }} {{ \Illuminate\Support\Str::plural('seat', $package->seats) }}</p>
                        @if ($package->description)<p class="mt-4 text-sm text-ink-600">{{ $package->description }}</p>@endif
                        @if ($package->benefits)
                            <ul class="mt-5 space-y-2 text-sm">
                                @foreach ($package->benefits as $benefit)
                                    <li class="flex gap-2"><svg class="mt-0.5 size-4 shrink-0 text-gold-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 011.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>{{ $benefit }}</li>
                                @endforeach
                            </ul>
                        @endif
                        <div class="mt-auto pt-6">
                            @if ($package->remaining() > 0)
                                <a href="{{ route('bookings.create', $package) }}" class="btn btn-gold w-full">Book {{ $package->name }}</a>
                            @else
                                <span class="btn btn-outline w-full opacity-60">Sold out</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.app>
