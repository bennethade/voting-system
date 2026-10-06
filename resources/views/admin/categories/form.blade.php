@php
    $editing = $category->exists;
@endphp

<x-layouts.admin :title="$editing ? 'Edit '.$category->name : 'New category'">
    <x-admin.page-header :title="$editing ? 'Edit category' : 'New category'">
        <x-slot:actions>
            <a href="{{ route('admin.categories.index', array_filter(['event' => $category->award_event_id])) }}" class="btn btn-outline">Back</a>
        </x-slot:actions>
    </x-admin.page-header>

    <form method="POST" action="{{ $editing ? route('admin.categories.update', $category) : route('admin.categories.store') }}" enctype="multipart/form-data" class="grid gap-6 xl:grid-cols-3">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="space-y-6 xl:col-span-2">
            <section class="card space-y-5 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">Details</h3>
                <x-select name="award_event_id" label="Award event" :options="$events" :value="$category->award_event_id" placeholder="Select an event" required />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-input name="name" label="Category name" :value="$category->name" required maxlength="150" />
                    <x-input name="slug" label="URL slug" :value="$category->slug" hint="Leave blank to generate from the name. Unique within the event." />
                </div>
                <x-textarea name="description" label="Description" :value="$category->description" rows="5" />
            </section>
        </div>

        <div class="space-y-6">
            <section class="card space-y-5 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">Publishing</h3>
                <x-select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="$category->status" required hint="Inactive categories are hidden from the public and cannot receive votes." />
                <x-input name="sort_order" type="number" min="0" label="Sort order" :value="$category->sort_order" hint="Lower numbers are shown first." />
            </section>

            <section class="card space-y-5 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">Image</h3>
                <x-admin.image-field name="image" label="Category image" :current="$category->image" hint="JPG, PNG or WebP. Max 2 MB." />
            </section>

            <button class="btn btn-gold btn-lg w-full">{{ $editing ? 'Save changes' : 'Create category' }}</button>
        </div>
    </form>
</x-layouts.admin>
