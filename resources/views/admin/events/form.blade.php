@php
    $editing = $event->exists;
    $dt = fn ($value) => $value?->format('Y-m-d\TH:i');
@endphp

<x-layouts.admin :title="$editing ? 'Edit '.$event->name : 'New award event'">
    <x-admin.page-header :title="$editing ? 'Edit award event' : 'New award event'">
        <x-slot:actions>
            <a href="{{ route('admin.events.index') }}" class="btn btn-outline">Back</a>
        </x-slot:actions>
    </x-admin.page-header>

    <form method="POST" action="{{ $editing ? route('admin.events.update', $event) : route('admin.events.store') }}" enctype="multipart/form-data" class="grid gap-6 xl:grid-cols-3">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="space-y-6 xl:col-span-2">
            <section class="card space-y-5 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">Details</h3>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-input name="name" label="Event name" :value="$event->name" required class="sm:col-span-2" />
                    <x-input name="slug" label="URL slug" :value="$event->slug" hint="Leave blank to generate from the name." />
                    <x-input name="venue" label="Venue" :value="$event->venue" />
                </div>
                <x-input name="short_description" label="Short description" :value="$event->short_description" hint="Shown on cards and in search results (max 500 characters)." />
                <x-textarea name="description" label="Full description" :value="$event->description" rows="6" />
            </section>

            <section class="card space-y-5 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">Dates</h3>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-input name="voting_start_at" type="datetime-local" label="Voting opens" :value="$dt($event->voting_start_at)" />
                    <x-input name="voting_end_at" type="datetime-local" label="Voting closes" :value="$dt($event->voting_end_at)" hint="Voting stops automatically at this time." />
                    <x-input name="event_date" type="datetime-local" label="Awards night" :value="$dt($event->event_date)" />
                    <x-input name="start_date" type="date" label="Season start date" :value="$event->start_date?->format('Y-m-d')" />
                </div>
            </section>

            <section class="card space-y-5 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">Voting rules</h3>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.toggle name="settings[free_voting_enabled]" label="Free voting" hint="Registered voters get free votes." :checked="$event->setting('free_voting_enabled')" />
                    <x-admin.toggle name="settings[free_vote_requires_verification]" label="Require verified email for free votes" :checked="$event->setting('free_vote_requires_verification')" />
                    <x-admin.toggle name="settings[paid_voting_enabled]" label="Paid voting" hint="Voters can buy vote packages." :checked="$event->setting('paid_voting_enabled')" />
                    <x-admin.toggle name="settings[paid_vote_requires_login]" label="Require sign-in to buy votes" hint="Off = guests can pay with just an email." :checked="$event->setting('paid_vote_requires_login')" />
                    <x-admin.toggle name="settings[booking_enabled]" label="Table bookings" :checked="$event->setting('booking_enabled')" />
                </div>
                <x-select name="settings[free_vote_scope]" label="Free vote allowance" :options="collect(\App\Enums\FreeVoteScope::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])" :value="$event->freeVoteScope()->value" required />
            </section>
        </div>

        <div class="space-y-6">
            <section class="card space-y-5 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">Publishing</h3>
                <x-select name="status" label="Status" :options="collect(\App\Enums\EventStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])" :value="$event->status" required hint="Draft events are hidden from the public." />
                <x-select name="results_visibility" label="Public results" :options="collect(\App\Enums\ResultsVisibility::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])" :value="$event->results_visibility" required />
                <x-admin.toggle name="is_featured" label="Feature on home page" :checked="$event->is_featured" />
            </section>

            <section class="card space-y-5 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">Images</h3>
                <x-admin.image-field name="logo" label="Logo" :current="$event->logo" hint="Max 2 MB." />
                <x-admin.image-field name="banner" label="Banner" :current="$event->banner" hint="At least 600px wide, max 4 MB." />
            </section>

            <button class="btn btn-gold btn-lg w-full">{{ $editing ? 'Save changes' : 'Create event' }}</button>
        </div>
    </form>
</x-layouts.admin>
