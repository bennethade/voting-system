@props(['packages', 'category', 'event'])

{{--
    Step 1 of paid voting. Submitting creates a pending order and leads to a
    server-rendered review page where the voter explicitly confirms the
    amount before any payment is initialised.
--}}
@php
    $packageData = $packages->map(fn ($p) => [
        'id' => $p->id,
        'name' => $p->name,
        'votes' => $p->votes,
        'amount' => money($p->amount, $event->currency()),
    ])->values();
    $actionTemplate = route('votes.checkout.store', '__NOMINEE__');
    $requiresLogin = $event->setting('paid_vote_requires_login') && ! auth()->check();
@endphp

<div x-data="paidVoteModal(@js($packageData), @js($actionTemplate))"
     @open-paid-vote.window="show($event.detail)"
     @keydown.escape.window="open = false"
     x-cloak>
    <div x-show="open" x-transition.opacity class="fixed inset-0 z-50 bg-ink-950/70 backdrop-blur-sm" @click="open = false"></div>

    <div x-show="open" x-transition
         class="fixed inset-x-0 bottom-0 z-50 max-h-[92vh] overflow-y-auto rounded-t-3xl bg-white shadow-2xl sm:inset-auto sm:top-1/2 sm:left-1/2 sm:w-full sm:max-w-xl sm:-translate-x-1/2 sm:-translate-y-1/2 sm:rounded-3xl"
         role="dialog" aria-modal="true" aria-labelledby="paid-vote-title">
        <div class="sticky top-0 flex items-start justify-between gap-4 border-b border-ink-100 bg-white px-5 py-4 sm:px-6">
            <div>
                <p class="eyebrow">{{ $category->name }}</p>
                <h2 id="paid-vote-title" class="mt-0.5 text-xl font-semibold">Buy votes for <span x-text="nominee.name"></span></h2>
            </div>
            <button type="button" @click="open = false" class="grid size-10 shrink-0 place-items-center rounded-full hover:bg-ink-50" aria-label="Close">&times;</button>
        </div>

        @if ($requiresLogin)
            <div class="p-6 text-center">
                <p class="text-ink-600">Please sign in to purchase votes for this event.</p>
                <a href="{{ route('login') }}" class="btn btn-ink mt-4">Sign in</a>
            </div>
        @elseif ($packages->isEmpty())
            <div class="p-6 text-center text-ink-600">No voting packages are available right now.</div>
        @else
            <form method="POST" :action="action" class="space-y-5 p-5 sm:p-6" x-data="submitOnce" @submit="submit">
                @csrf
                <fieldset>
                    <legend class="label">Choose a package</legend>
                    <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3">
                        <template x-for="pkg in packages" :key="pkg.id">
                            <label class="cursor-pointer rounded-2xl border-2 p-3 text-center transition"
                                   :class="Number(selectedId) === pkg.id ? 'border-gold-400 bg-gold-50 ring-4 ring-gold-100' : 'border-ink-100 hover:border-ink-300'">
                                <input type="radio" name="package_id" :value="pkg.id" x-model="selectedId" class="sr-only" required>
                                <span class="block font-display text-lg font-semibold text-ink-900" x-text="pkg.amount"></span>
                                <span class="mt-0.5 block text-sm text-ink-500"><span x-text="pkg.votes.toLocaleString()"></span> <span x-text="pkg.votes === 1 ? 'vote' : 'votes'"></span></span>
                            </label>
                        </template>
                    </div>
                </fieldset>

                <div x-show="selected" x-transition class="rounded-2xl bg-ink-950 p-4 text-white">
                    <p class="text-xs tracking-widest text-ink-300 uppercase">You are purchasing</p>
                    <div class="mt-1 flex items-end justify-between gap-3">
                        <p class="font-display text-2xl font-semibold"><span x-text="selected?.votes.toLocaleString()"></span> votes</p>
                        <p class="text-right"><span class="block text-xs text-ink-300">Amount</span><span class="font-display text-2xl font-semibold text-gold-300" x-text="selected?.amount"></span></p>
                    </div>
                </div>

                @guest
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-input name="name" label="Your name" autocomplete="name" required />
                        <x-input name="email" type="email" label="Email for receipt" autocomplete="email" required />
                        <x-input name="phone" type="tel" label="Phone (optional)" autocomplete="tel" class="sm:col-span-2" />
                    </div>
                @else
                    <input type="hidden" name="name" value="{{ auth()->user()->name }}">
                    <p class="text-sm text-ink-500">Receipt will be sent to <strong class="text-ink-800">{{ auth()->user()->email }}</strong>.</p>
                @endguest

                <button type="submit" class="btn btn-gold btn-lg w-full" :disabled="!selected || submitting">
                    Continue to review
                </button>
                <p class="text-center text-xs text-ink-400">You'll confirm the exact amount on the next screen before paying securely with Paystack.</p>
            </form>
        @endif
    </div>
</div>
