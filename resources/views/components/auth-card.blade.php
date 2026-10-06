@props(['title', 'subtitle' => null])

<section class="hero-glow -mt-px">
    <div class="container-page flex min-h-[calc(100vh-4rem)] items-center justify-center py-12">
        <div class="w-full max-w-md">
            <div class="mb-6 text-center text-white">
                <h1 class="text-3xl font-semibold sm:text-4xl">{{ $title }}</h1>
                @if ($subtitle)
                    <p class="mt-2 text-sm text-ink-200">{{ $subtitle }}</p>
                @endif
            </div>
            <div class="card p-6 sm:p-8">
                {{ $slot }}
            </div>
            @isset($footer)
                <div class="mt-6 text-center text-sm text-ink-200">{{ $footer }}</div>
            @endisset
        </div>
    </div>
</section>
