<x-layouts.app :title="'Buy votes for '.$nominee->name" :noindex="true">
    <x-page-header :eyebrow="$category->name" :title="'Buy votes for '.$nominee->name" subtitle="Step 1 of 2 — choose your package." />

    <div class="container-page mt-10 max-w-3xl">
        @if ($requiresLogin)
            <x-empty-state title="Sign in required" message="Please sign in to purchase votes for this event.">
                <a href="{{ route('login', ['return' => request()->getRequestUri()]) }}" class="btn btn-ink">Sign in</a>
            </x-empty-state>
        @else
            @php
                $packageData = $packages->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'votes' => $p->votes, 'amount' => money($p->amount, $event->currency())])->values();
            @endphp
            <form method="POST" action="{{ route('votes.checkout.store', $nominee) }}" class="card space-y-6 p-5 sm:p-8"
                  x-data="packagePicker(@js($packageData), @js(old('package_id', $selected ?: null)))">
                @csrf
                <fieldset>
                    <legend class="label">Voting package</legend>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @foreach ($packages as $package)
                            <label class="cursor-pointer rounded-2xl border-2 p-4 text-center transition"
                                   :class="Number(selectedId) === {{ $package->id }} ? 'border-gold-400 bg-gold-50 ring-4 ring-gold-100' : 'border-ink-100 hover:border-ink-300'">
                                <input type="radio" name="package_id" value="{{ $package->id }}" x-model="selectedId" class="sr-only" required>
                                <span class="block font-display text-xl font-semibold">{{ money($package->amount, $event->currency()) }}</span>
                                <span class="mt-1 block text-sm text-ink-500">{{ number_format($package->votes) }} {{ \Illuminate\Support\Str::plural('vote', $package->votes) }}</span>
                                @if ($package->description)<span class="mt-1 block text-xs text-ink-400">{{ $package->description }}</span>@endif
                            </label>
                        @endforeach
                    </div>
                    @error('package_id') <p class="field-error">{{ $message }}</p> @enderror
                </fieldset>

                <div x-show="selected" x-cloak class="rounded-2xl bg-ink-950 p-5 text-white">
                    <p class="text-xs tracking-widest text-ink-300 uppercase">You are purchasing</p>
                    <div class="mt-1 flex items-end justify-between">
                        <p class="font-display text-3xl font-semibold"><span x-text="selected?.votes.toLocaleString()"></span> votes</p>
                        <p class="text-right"><span class="block text-xs text-ink-300">Amount</span><span class="font-display text-3xl font-semibold text-gold-300" x-text="selected?.amount"></span></p>
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
                @endguest

                <button type="submit" class="btn btn-gold btn-lg w-full" :disabled="!selected">Continue to review</button>
            </form>
        @endif
    </div>
</x-layouts.app>
