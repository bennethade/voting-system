@php
    $editing = $item->exists;
@endphp

<x-layouts.admin :title="$editing ? 'Edit '.$item->title : 'New gallery item'">
    <x-admin.page-header :title="$editing ? 'Edit gallery item' : 'New gallery item'">
        <x-slot:actions>
            <a href="{{ route('admin.gallery.index') }}" class="btn btn-outline">Back</a>
        </x-slot:actions>
    </x-admin.page-header>

    <form method="POST" action="{{ $editing ? route('admin.gallery.update', $item) : route('admin.gallery.store') }}" enctype="multipart/form-data"
          x-data="{ type: @js(old('type', $item->type)) }" class="grid gap-6 xl:grid-cols-3">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="space-y-6 xl:col-span-2">
            <section class="card space-y-5 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">Details</h3>
                <x-input name="title" label="Title" :value="$item->title" required maxlength="150" />
                <x-input name="caption" label="Caption" :value="$item->caption" maxlength="255" hint="Short line shown under the item." />
                <x-textarea name="description" label="Description" :value="$item->description" rows="4" maxlength="2000" />
            </section>

            <section class="card space-y-5 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">Media</h3>
                <x-select name="type" label="Type" :options="\App\Models\GalleryItem::TYPES" :value="$item->type" required x-model="type" />

                <div x-show="type === 'image'" x-cloak>
                    <x-admin.image-field name="image" label="Image" :current="$item->type === 'image' ? $item->file_path : null" hint="JPG, PNG or WebP, max 5 MB." />
                </div>

                <div x-show="type === 'video'" x-cloak class="space-y-5">
                    <div>
                        <label for="video" class="label">Video file</label>
                        @if ($item->type === 'video' && $item->file_path)
                            <video src="{{ media_url($item->file_path) }}" controls preload="metadata" class="mb-3 w-full max-w-md rounded-xl bg-ink-900"></video>
                        @endif
                        <input id="video" name="video" type="file" accept="video/mp4,video/webm"
                               class="block w-full text-sm text-ink-600 file:mr-3 file:rounded-full file:border-0 file:bg-ink-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-ink-700">
                        <p class="hint">MP4 or WebM, max 50 MB.{{ $item->type === 'video' && $item->file_path ? ' Leave empty to keep the current video.' : '' }}</p>
                        @error('video') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div x-show="type === 'youtube'" x-cloak>
                    <x-input name="youtube_url" type="url" label="YouTube link" :value="$item->youtube_url" placeholder="https://www.youtube.com/watch?v=…" hint="Watch, youtu.be, embed and Shorts links are accepted." />
                </div>

                <div x-show="type !== 'image'" x-cloak>
                    <x-admin.image-field name="thumbnail" label="Thumbnail (optional)" :current="$item->thumbnail" hint="Shown before the video plays. YouTube items use YouTube's own thumbnail if empty. Max 2 MB." />
                </div>
            </section>
        </div>

        <div class="space-y-6">
            <section class="card space-y-5 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">Publishing</h3>
                <x-select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="$item->status" required hint="Inactive items are hidden from the public gallery." />
                <x-select name="award_event_id" label="Award event" :options="$events" :value="$item->award_event_id" placeholder="Not linked to an event" />
                <x-input name="sort_order" type="number" min="0" label="Display order" :value="$item->sort_order" hint="Lower numbers appear first." />
            </section>

            <button class="btn btn-gold btn-lg w-full">{{ $editing ? 'Save changes' : 'Add to gallery' }}</button>
        </div>
    </form>
</x-layouts.admin>
