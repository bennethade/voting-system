<x-layouts.app :title="'Book '.$package->name" :noindex="true">
    <x-page-header :eyebrow="$event->name" :title="'Book '.$package->name" :subtitle="money($package->price, $event->currency()).' · '.$package->seats.' '.\Illuminate\Support\Str::plural('seat', $package->seats).' per '.\Illuminate\Support\Str::lower($package->name)" />

    <div class="container-page mt-10 max-w-3xl">
        <form method="POST" action="{{ route('bookings.store', $package) }}" class="card space-y-6 p-5 sm:p-8"
              x-data="{ qty: {{ (int) old('quantity', 1) }}, seats: {{ $package->seats }}, price: {{ to_subunit($package->price) }}, max: {{ min(20, $package->remaining()) }} }">
            @csrf
            <div>
                <label class="label" for="quantity">Quantity</label>
                <div class="flex items-center gap-3">
                    <button type="button" class="btn btn-outline size-11 p-0 text-lg" @click="qty = Math.max(1, qty - 1)" aria-label="Decrease">−</button>
                    <input id="quantity" name="quantity" type="number" min="1" :max="max" x-model.number="qty" class="input w-20 text-center" required>
                    <button type="button" class="btn btn-outline size-11 p-0 text-lg" @click="qty = Math.min(max, qty + 1)" aria-label="Increase">+</button>
                    <span class="text-sm text-ink-500">{{ $package->remaining() }} available</span>
                </div>
                @error('quantity') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="rounded-2xl bg-ink-950 p-5 text-white">
                <div class="flex items-end justify-between">
                    <p><span class="block text-xs tracking-widest text-ink-300 uppercase">Seats</span><span class="font-display text-3xl font-semibold" x-text="qty * seats"></span></p>
                    <p class="text-right"><span class="block text-xs tracking-widest text-ink-300 uppercase">Estimated total</span><span class="font-display text-3xl font-semibold text-gold-300" x-text="'{{ config('awards.currency_symbols.'.$event->currency(), '') }}' + (qty * price / 100).toLocaleString()"></span></p>
                </div>
                <p class="mt-2 text-xs text-ink-400">The final amount is confirmed on the next screen.</p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-input name="name" label="Contact name" autocomplete="name" required :value="auth()->user()?->name" />
                <x-input name="email" type="email" label="Contact email" autocomplete="email" required :value="auth()->user()?->email" />
                <x-input name="phone" type="tel" label="Phone" autocomplete="tel" required :value="auth()->user()?->phone" />
                <x-input name="organization" label="Company / organisation (optional)" autocomplete="organization" />
            </div>

            <div>
                <p class="label">Attendee names <span class="font-normal text-ink-400">(optional — you can send these later)</span></p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <template x-for="i in Math.min(qty * seats, 40)" :key="i">
                        <input type="text" :name="`attendees[${i - 1}][name]`" :placeholder="`Guest ${i} name`" class="input" maxlength="120">
                    </template>
                </div>
            </div>

            <x-textarea name="notes" label="Notes (optional)" rows="3" hint="Dietary requirements, accessibility needs, seating requests…" />

            <button type="submit" class="btn btn-gold btn-lg w-full">Continue to review</button>
        </form>
    </div>
</x-layouts.app>
