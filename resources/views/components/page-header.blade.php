@props(['title', 'eyebrow' => null, 'subtitle' => null])

<section class="hero-glow text-white">
    <div class="container-page py-12 sm:py-16">
        @isset($breadcrumbs)
            <nav class="mb-4 text-sm text-ink-300" aria-label="Breadcrumb">{{ $breadcrumbs }}</nav>
        @endisset
        @if ($eyebrow)
            <p class="eyebrow text-gold-300">{{ $eyebrow }}</p>
        @endif
        <h1 class="mt-2 text-3xl font-semibold sm:text-5xl">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-3 max-w-2xl text-ink-200">{{ $subtitle }}</p>
        @endif
        {{ $slot }}
    </div>
</section>
