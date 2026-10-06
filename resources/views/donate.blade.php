<x-layouts.app title="Donate" description="Support the awards and the creative community behind it.">
    <x-page-header title="Support the awards" subtitle="Your donation helps us celebrate and grow the creator community." />

    <div class="container-page mt-10 max-w-2xl">
        <form method="POST" action="{{ route('donate.store') }}" class="card space-y-6 p-5 sm:p-8"
              x-data="{ amount: @js(old('amount', $presets[1] ?? $presets[0] ?? '')), custom: @js(old('custom_amount', '')) }">
            @csrf
            <fieldset>
                <legend class="label">Choose an amount</legend>
                <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3">
                    @foreach ($presets as $preset)
                        <label class="cursor-pointer rounded-2xl border-2 px-3 py-4 text-center font-display text-lg font-semibold transition"
                               :class="!custom && Number(amount) === {{ $preset }} ? 'border-gold-400 bg-gold-50' : 'border-ink-100 hover:border-ink-300'">
                            <input type="radio" name="amount" value="{{ $preset }}" x-model="amount" @change="custom = ''" class="sr-only">
                            {{ money($preset) }}
                        </label>
                    @endforeach
                </div>
            </fieldset>
            <x-input name="custom_amount" type="text" inputmode="decimal" label="Or enter a custom amount" x-model="custom" :hint="'Minimum '.money($minimum)" placeholder="e.g. 7500" />
            @error('amount') <p class="field-error -mt-4">{{ $message }}</p> @enderror

            <div class="grid gap-5 sm:grid-cols-2">
                <x-input name="name" label="Your name" autocomplete="name" required :value="auth()->user()?->name" />
                @guest
                    <x-input name="email" type="email" label="Email" autocomplete="email" required />
                @endguest
            </div>
            <x-textarea name="message" label="Message (optional)" rows="3" />
            <label class="flex items-center gap-2.5 text-sm text-ink-700">
                <input type="checkbox" name="anonymous" value="1" class="size-4 rounded border-ink-300 text-gold-500" @checked(old('anonymous'))>
                Keep my donation anonymous
            </label>
            <button type="submit" class="btn btn-gold btn-lg w-full">Continue</button>
        </form>
    </div>
</x-layouts.app>
