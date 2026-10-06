<x-layouts.app title="Create an account" :noindex="true">
    <x-auth-card title="Join the vote" subtitle="Create a free account to cast your free vote.">
        <form method="POST" action="{{ route('register.store') }}" class="space-y-5" x-data="submitOnce" @submit="submit">
            @csrf
            <x-input name="name" label="Full name" autocomplete="name" required autofocus />
            <x-input name="email" type="email" label="Email address" autocomplete="email" required hint="We'll send a link to verify this address." />
            <x-input name="phone" type="tel" label="Phone number" autocomplete="tel" inputmode="tel" hint="Optional" />
            <x-input name="password" type="password" label="Password" autocomplete="new-password" required hint="At least 8 characters, with letters and numbers." />
            <x-input name="password_confirmation" type="password" label="Confirm password" autocomplete="new-password" required />

            {{-- Honeypot field: hidden from people, tempting to bots --}}
            <div class="hidden" aria-hidden="true">
                <label for="website">Website</label>
                <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
            </div>

            <div>
                <label class="flex items-start gap-2.5 text-sm text-ink-700">
                    <input type="checkbox" name="terms" value="1" class="mt-0.5 size-4 rounded border-ink-300 text-gold-500 focus:ring-gold-400" @checked(old('terms'))>
                    <span>I agree to the <a href="{{ route('terms') }}" target="_blank" class="font-medium text-gold-700 underline">terms</a> and <a href="{{ route('privacy') }}" target="_blank" class="font-medium text-gold-700 underline">privacy policy</a>.</span>
                </label>
                @error('terms') <p class="field-error">{{ $message }}</p> @enderror
                @error('website') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="btn btn-gold btn-lg w-full" :disabled="submitting">Create account</button>
        </form>
        <x-slot:footer>
            Already registered? <a href="{{ route('login') }}" class="font-semibold text-gold-300 hover:underline">Sign in</a>
        </x-slot:footer>
    </x-auth-card>
</x-layouts.app>
