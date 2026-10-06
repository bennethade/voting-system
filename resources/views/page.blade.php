<x-layouts.app :title="$page->meta_title ?: $page->title" :description="$page->meta_description">
    <x-page-header :title="$page->title" />
    <article class="container-page mt-10 max-w-3xl">
        <div class="prose-content">
            {{-- Stored content is sanitised with HTMLPurifier when saved (App\Services\HtmlSanitizer). --}}
            {!! $page->content !!}
        </div>
        <p class="mt-10 text-sm text-ink-400">Last updated {{ $page->updated_at->format('j F Y') }}</p>
    </article>
</x-layouts.app>
