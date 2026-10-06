<x-layouts.app title="Gallery" description="Photos and videos from our award events.">
    <x-page-header title="Gallery" subtitle="Moments from the red carpet, the stage and behind the scenes." />

    <div class="container-page mt-8" x-data="{ lightbox: null }">
        <form method="GET" class="flex flex-wrap gap-2">
            <a href="{{ route('gallery') }}" @class(['rounded-full px-4 py-2 text-sm font-medium', 'bg-ink-900 text-white' => ! $selected && ! request('type'), 'bg-white border border-ink-200' => $selected || request('type')])>All</a>
            @foreach ($events as $e)
                <a href="{{ route('gallery', ['event' => $e->slug]) }}" @class(['rounded-full px-4 py-2 text-sm font-medium', 'bg-ink-900 text-white' => $selected?->id === $e->id, 'bg-white border border-ink-200' => $selected?->id !== $e->id])>{{ $e->name }}</a>
            @endforeach
            <a href="{{ route('gallery', array_filter(['event' => $selected?->slug, 'type' => 'youtube'])) }}" @class(['rounded-full px-4 py-2 text-sm font-medium', 'bg-ink-900 text-white' => request('type') === 'youtube', 'bg-white border border-ink-200' => request('type') !== 'youtube'])>Videos</a>
        </form>

        @if ($items->isEmpty())
            <x-empty-state class="mt-8" title="No gallery items yet" />
        @else
            <div class="mt-8 columns-1 gap-4 sm:columns-2 lg:columns-3 [&>*]:mb-4">
                @foreach ($items as $item)
                    <figure class="card break-inside-avoid overflow-hidden">
                        @if ($item->type === 'youtube' && $item->youtubeId())
                            <div class="aspect-video">
                                <iframe class="size-full" src="https://www.youtube-nocookie.com/embed/{{ $item->youtubeId() }}" title="{{ $item->title }}" loading="lazy" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                            </div>
                        @elseif ($item->type === 'video' && $item->file_path)
                            <video class="w-full" controls preload="metadata" @if ($item->thumbnail) poster="{{ media_url($item->thumbnail) }}" @endif>
                                <source src="{{ media_url($item->file_path) }}">
                            </video>
                        @elseif ($item->file_path)
                            <button type="button" class="block w-full" @click="lightbox = { src: @js(media_url($item->file_path)), title: @js($item->title) }">
                                <img src="{{ media_url($item->file_path) }}" alt="{{ $item->title }}" loading="lazy" class="w-full">
                            </button>
                        @endif
                        <figcaption class="p-4">
                            <p class="font-medium">{{ $item->title }}</p>
                            @if ($item->caption)<p class="mt-0.5 text-sm text-ink-500">{{ $item->caption }}</p>@endif
                        </figcaption>
                    </figure>
                @endforeach
            </div>
            <div class="mt-8">{{ $items->links() }}</div>
        @endif

        <div x-cloak x-show="lightbox" x-transition.opacity @keydown.escape.window="lightbox = null" @click="lightbox = null" class="fixed inset-0 z-50 grid place-items-center bg-ink-950/90 p-4">
            <figure class="max-h-full max-w-5xl" @click.stop>
                <img :src="lightbox?.src" :alt="lightbox?.title" class="max-h-[85vh] rounded-2xl">
                <figcaption class="mt-3 text-center text-white" x-text="lightbox?.title"></figcaption>
            </figure>
        </div>
    </div>
</x-layouts.app>
