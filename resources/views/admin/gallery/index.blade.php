<x-layouts.admin title="Gallery">
    <x-admin.page-header title="Gallery" subtitle="Photos and videos shown in the public gallery.">
        <x-slot:actions>
            <a href="{{ route('admin.gallery.create') }}" class="btn btn-gold">Add item</a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.filters placeholder="Search title or caption…">
        <x-select name="event" label="Event" :options="$events" :value="request('event')" placeholder="All events" class="[&_label]:text-xs" />
        <x-select name="type" label="Type" :options="\App\Models\GalleryItem::TYPES" :value="request('type')" placeholder="All types" class="[&_label]:text-xs" />
        <x-select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="request('status')" placeholder="All statuses" class="[&_label]:text-xs" />
    </x-admin.filters>

    <div class="mb-3 flex flex-wrap items-center gap-3 text-xs text-ink-500">
        <span>Sort:</span>
        <x-admin.sort-link column="sort_order" label="Display order" />
        <x-admin.sort-link column="title" label="Title" />
        <x-admin.sort-link column="created_at" label="Date added" />
    </div>

    @if ($items->isEmpty())
        <x-empty-state title="No gallery items" message="Upload photos, video files or YouTube links to build the gallery.">
            <a href="{{ route('admin.gallery.create') }}" class="btn btn-gold btn-sm">Add item</a>
        </x-empty-state>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($items as $item)
                @php
                    $thumb = match ($item->type) {
                        'youtube' => $item->youtubeId() ? 'https://img.youtube.com/vi/'.$item->youtubeId().'/mqdefault.jpg' : null,
                        'video' => media_url($item->thumbnail),
                        default => media_url($item->thumbnail ?: $item->file_path),
                    };
                @endphp
                <div class="card flex flex-col overflow-hidden">
                    <a href="{{ route('admin.gallery.edit', $item) }}" class="relative block aspect-video bg-ink-100">
                        @if ($thumb)
                            <img src="{{ $thumb }}" alt="{{ $item->title }}" loading="lazy" class="size-full object-cover">
                        @else
                            <span class="grid size-full place-items-center text-xs text-ink-400">{{ $item->type === 'video' ? 'Video (no thumbnail)' : 'No preview' }}</span>
                        @endif
                        <span class="badge absolute top-2 left-2 bg-ink-900/80 text-white">{{ \App\Models\GalleryItem::TYPES[$item->type] ?? $item->type }}</span>
                    </a>
                    <div class="flex flex-1 flex-col gap-2 p-4">
                        <div class="flex items-start justify-between gap-2">
                            <a href="{{ route('admin.gallery.edit', $item) }}" class="font-semibold text-ink-900 hover:text-gold-700">{{ $item->title }}</a>
                            <x-status-badge :status="$item->status" class="shrink-0" />
                        </div>
                        @if ($item->caption)<p class="line-clamp-2 text-sm text-ink-500">{{ $item->caption }}</p>@endif
                        <p class="mt-auto text-xs text-ink-400">
                            {{ $item->awardEvent?->name ?? 'No event' }} · Order {{ $item->sort_order }}
                        </p>
                        <div class="flex items-center justify-between border-t border-ink-100 pt-3">
                            <a href="{{ route('admin.gallery.edit', $item) }}" class="text-sm font-medium text-ink-700 hover:text-ink-900">Edit</a>
                            <x-admin.confirm-form :action="route('admin.gallery.destroy', $item)" method="DELETE" :confirm="'Delete '.$item->title.'? This cannot be undone.'" />
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="mt-6">{{ $items->links() }}</div>
</x-layouts.admin>
