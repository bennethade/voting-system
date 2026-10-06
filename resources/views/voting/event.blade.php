<x-layouts.app :title="'Vote — '.$event->name" :description="$event->short_description ?: 'Choose a category and vote for your favourite nominees in '.$event->name.'.'" :image="media_url($event->banner)">
    <x-page-header :eyebrow="$event->name" title="Choose a category" subtitle="Pick a category to see its nominees, then cast a free vote or buy a vote package.">
        <div class="mt-6 max-w-xl"><x-voting-status :event="$event" /></div>
    </x-page-header>

    <div class="container-page mt-10">
        @if ($categories->isEmpty())
            <x-empty-state title="Categories coming soon" message="Nominees for this event haven't been announced yet." />
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($categories as $category)
                    <a href="{{ route('voting.category', [$event, $category]) }}" class="group card overflow-hidden transition hover:border-gold-300 hover:shadow-lg">
                        @if ($category->image)
                            <img src="{{ media_url($category->image) }}" alt="" loading="lazy" class="aspect-[16/9] w-full object-cover">
                        @endif
                        <div class="flex items-center justify-between gap-4 p-5">
                            <div class="min-w-0">
                                <h2 class="font-sans text-lg font-semibold group-hover:text-gold-700">{{ $category->name }}</h2>
                                <p class="mt-0.5 text-sm text-ink-500">{{ $category->nominees_count }} {{ \Illuminate\Support\Str::plural('nominee', $category->nominees_count) }}</p>
                            </div>
                            <span class="btn btn-gold btn-sm shrink-0">Vote</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.app>
