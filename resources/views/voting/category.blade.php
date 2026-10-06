<x-layouts.app :title="$category->name.' — '.$event->name" :description="$category->description ?: 'Vote for the '.$category->name.' at '.$event->name.'.'" :image="media_url($category->image)">
    <x-page-header :eyebrow="$event->name" :title="$category->name" :subtitle="$category->description">
        <x-slot:breadcrumbs>
            <a href="{{ route('voting.event', $event) }}" class="hover:text-white">All categories</a> <span aria-hidden="true">/</span> <span class="text-ink-100">{{ $category->name }}</span>
        </x-slot:breadcrumbs>
        <div class="mt-6 max-w-xl"><x-voting-status :event="$event" /></div>
    </x-page-header>

    <div class="container-page mt-8">
        @auth
            @if ($votingOpen && $event->setting('free_voting_enabled') && $event->setting('free_vote_requires_verification') && ! auth()->user()->hasVerifiedEmail())
                <div class="mb-6 flex flex-col gap-3 rounded-2xl border border-gold-200 bg-gold-50 p-4 text-sm text-gold-900 sm:flex-row sm:items-center sm:justify-between">
                    <p>Verify your email address to unlock your free vote.</p>
                    <a href="{{ route('verification.notice') }}" class="btn btn-gold btn-sm">Verify email</a>
                </div>
            @endif
        @endauth

        @if ($nominees->isEmpty())
            <x-empty-state title="No nominees yet" message="Nominees for this category will be announced soon." />
        @else
            <div class="grid grid-cols-1 gap-4 min-[480px]:grid-cols-2 sm:gap-5 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($nominees as $nominee)
                    <x-nominee-card :nominee="$nominee" :category="$category" :event="$event" :voting-open="$votingOpen" :show-counts="$showCounts" :free-vote-available="$freeVoteAvailable" />
                @endforeach
            </div>
        @endif

        @if ($otherCategories->isNotEmpty())
            <div class="mt-16">
                <h2 class="font-sans text-lg font-semibold">More categories</h2>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($otherCategories as $other)
                        <a href="{{ route('voting.category', [$event, $other]) }}" class="rounded-full border border-ink-200 bg-white px-4 py-2 text-sm font-medium hover:border-gold-400 hover:text-gold-800">{{ $other->name }}</a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    @if ($votingOpen && $event->setting('paid_voting_enabled'))
        <x-paid-vote-modal :packages="$packages" :category="$category" :event="$event" />
    @endif
</x-layouts.app>
