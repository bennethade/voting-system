@php
    $editing = $nominee->exists;
    $selectedCategory = (string) old('category_id', $nominee->category_id);
    $networkLabels = ['instagram' => 'Instagram', 'x' => 'X (Twitter)', 'tiktok' => 'TikTok', 'youtube' => 'YouTube', 'facebook' => 'Facebook', 'linkedin' => 'LinkedIn'];
@endphp

<x-layouts.admin :title="$editing ? 'Edit '.$nominee->name : 'New nominee'">
    <x-admin.page-header :title="$editing ? 'Edit nominee' : 'New nominee'">
        <x-slot:actions>
            <a href="{{ route('admin.nominees.index', array_filter(['category' => $nominee->category_id])) }}" class="btn btn-outline">Back</a>
        </x-slot:actions>
    </x-admin.page-header>

    <form method="POST" action="{{ $editing ? route('admin.nominees.update', $nominee) : route('admin.nominees.store') }}" enctype="multipart/form-data" class="grid gap-6 xl:grid-cols-3">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="space-y-6 xl:col-span-2">
            <section class="card space-y-5 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">Details</h3>

                <div>
                    <label for="category_id" class="label">Category <span class="text-rose-500">*</span></label>
                    <select id="category_id" name="category_id" required @class(['input pr-9', 'input-error' => $errors->has('category_id')])>
                        <option value="">Select a category</option>
                        @foreach ($categoryGroups as $eventName => $options)
                            <optgroup label="{{ $eventName }}">
                                @foreach ($options as $id => $name)
                                    <option value="{{ $id }}" @selected($selectedCategory === (string) $id)>{{ $name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @error('category_id') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-input name="name" label="Nominee name" :value="$nominee->name" required maxlength="150" />
                    <x-input name="slug" label="URL slug" :value="$nominee->slug" hint="Leave blank to generate from the name. Must be unique across all events." />
                </div>
                <x-input name="short_description" label="Short description" :value="$nominee->short_description" maxlength="500" hint="Shown on nominee cards (max 500 characters)." />
                <x-textarea name="bio" label="Biography" :value="$nominee->bio" rows="8" hint="Plain text, up to 10,000 characters. Line breaks are kept." />
            </section>

            <section class="card space-y-5 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">Links</h3>
                <x-input name="website" type="url" label="Website" :value="$nominee->website" placeholder="https://" />
                <div class="grid gap-5 sm:grid-cols-2">
                    @foreach (\App\Models\Nominee::SOCIAL_NETWORKS as $network)
                        <x-input :name="'social_links['.$network.']'" type="url" :label="$networkLabels[$network] ?? ucfirst($network)" :value="$nominee->social_links[$network] ?? null" placeholder="https://" />
                    @endforeach
                </div>
            </section>
        </div>

        <div class="space-y-6">
            <section class="card space-y-5 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">Publishing</h3>
                <x-select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="$nominee->status" required hint="Inactive nominees are hidden and cannot receive votes." />
                <x-input name="sort_order" type="number" min="0" label="Sort order" :value="$nominee->sort_order" hint="Lower numbers are shown first." />
                @if ($editing)
                    <div>
                        <p class="label">Total votes</p>
                        <p class="font-display text-2xl font-semibold tabular-nums text-ink-900">{{ number_format($nominee->total_votes) }}</p>
                        <p class="hint">Read-only. Totals change only through votes or audited adjustments.</p>
                    </div>
                @endif
            </section>

            <section class="card space-y-5 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">Images</h3>
                <x-admin.image-field name="image" label="Profile photo" :current="$nominee->image" hint="Square works best. Max 3 MB." />
                <x-admin.image-field name="cover_image" label="Cover image" :current="$nominee->cover_image" hint="Wide banner for the profile page. Max 4 MB." />
            </section>

            <button class="btn btn-gold btn-lg w-full">{{ $editing ? 'Save changes' : 'Create nominee' }}</button>
        </div>
    </form>
</x-layouts.admin>
