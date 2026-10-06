@php
    $editing = $page->exists;
@endphp

<x-layouts.admin :title="$editing ? 'Edit '.$page->title : 'New page'">
    <x-admin.page-header :title="$editing ? 'Edit page' : 'New page'">
        <x-slot:actions>
            @if ($editing && isset($publicUrl))
                <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="btn btn-outline">View page</a>
            @endif
            <a href="{{ route('admin.pages.index') }}" class="btn btn-outline">Back</a>
        </x-slot:actions>
    </x-admin.page-header>

    <form method="POST" action="{{ $editing ? route('admin.pages.update', $page) : route('admin.pages.store') }}" class="grid gap-6 xl:grid-cols-3">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="space-y-6 xl:col-span-2">
            <section class="card space-y-5 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">Content</h3>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-input name="title" label="Title" :value="$page->title" required maxlength="150" />
                    @if ($page->is_system)
                        <div>
                            <span class="label">URL slug</span>
                            <p class="input bg-ink-50 text-ink-500">/{{ $page->slug }}</p>
                            <p class="hint">System page: the slug is fixed.</p>
                        </div>
                    @else
                        <x-input name="slug" label="URL slug" :value="$page->slug" maxlength="160" hint="Leave blank to generate from the title." />
                    @endif
                </div>
                <x-textarea name="content" label="Page content (HTML)" :value="$page->content" rows="22" class="[&_textarea]:font-mono [&_textarea]:text-sm"
                    hint="Allowed tags: h2, h3, h4, p, br, strong, em, u, s, blockquote, ul, ol, li, a (href, title, target), img (src, alt, width, height), hr, table, thead, tbody, tr, th, td, span, div. Anything else (scripts, styles, iframes, event attributes) is removed on save." />
            </section>
        </div>

        <div class="space-y-6">
            <section class="card space-y-5 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">Publishing</h3>
                <x-select name="status" label="Status" :options="['published' => 'Published', 'draft' => 'Draft']" :value="$page->status" required hint="Draft pages are hidden from the public." />
                @if ($page->is_system)
                    <p class="text-xs text-ink-500"><span class="badge bg-ink-900 text-white">System</span> This page is linked from the site layout and cannot be deleted.</p>
                @endif
            </section>

            <section class="card space-y-5 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">SEO</h3>
                <x-input name="meta_title" label="Meta title" :value="$page->meta_title" maxlength="150" hint="Defaults to the page title." />
                <x-textarea name="meta_description" label="Meta description" :value="$page->meta_description" rows="3" maxlength="300" hint="Shown in search results (max 300 characters)." />
            </section>

            <button class="btn btn-gold btn-lg w-full">{{ $editing ? 'Save changes' : 'Create page' }}</button>
        </div>
    </form>
</x-layouts.admin>
