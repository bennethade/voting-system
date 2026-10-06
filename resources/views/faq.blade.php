@php
    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $faqs->flatten()->map(fn ($faq) => [
            '@type' => 'Question',
            'name' => $faq->question,
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq->answer],
        ])->values()->all(),
    ];
@endphp

<x-layouts.app title="Frequently asked questions" description="Answers to common questions about voting, payments and the awards." :json-ld="$jsonLd">
    <x-page-header title="Frequently asked questions" subtitle="Everything you need to know about voting and the awards." />

    <div class="container-page mt-10 max-w-3xl">
        @forelse ($faqs as $group => $items)
            <section class="mb-10">
                <h2 class="mb-4 text-xl font-semibold">{{ $group }}</h2>
                <div class="card divide-y divide-ink-100">
                    @foreach ($items as $faq)
                        <div x-data="{ open: false }">
                            <button type="button" @click="open = !open" class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left font-medium" :aria-expanded="open">
                                {{ $faq->question }}
                                <span class="grid size-7 shrink-0 place-items-center rounded-full bg-ink-50 transition" :class="open && 'rotate-45 bg-gold-100'" aria-hidden="true">+</span>
                            </button>
                            <div x-show="open" x-collapse>
                                <div class="px-5 pb-5 text-ink-600">{!! nl2br(e($faq->answer)) !!}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @empty
            <x-empty-state title="No FAQs yet" />
        @endforelse

        <div class="card mt-6 flex flex-col items-center gap-3 p-6 text-center sm:flex-row sm:justify-between sm:text-left">
            <p class="font-medium">Still have a question?</p>
            <a href="{{ route('contact') }}" class="btn btn-ink">Contact us</a>
        </div>
    </div>
</x-layouts.app>
