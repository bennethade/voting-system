<x-layouts.app title="Contact us" description="Get in touch with the awards team.">
    <x-page-header title="Contact us" subtitle="Questions about voting, payments, partnerships or tickets? We're here to help." />

    <div class="container-page mt-10 grid gap-10 lg:grid-cols-3">
        <form method="POST" action="{{ route('contact.store') }}" class="card space-y-5 p-5 sm:p-8 lg:col-span-2" x-data="submitOnce" @submit="submit">
            @csrf
            <input type="hidden" name="form_token" value="{{ \Illuminate\Support\Facades\Crypt::encryptString((string) time()) }}">
            <div class="hidden" aria-hidden="true"><label for="company">Company</label><input type="text" id="company" name="company" tabindex="-1" autocomplete="off"></div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-input name="name" label="Your name" autocomplete="name" required :value="auth()->user()?->name" />
                <x-input name="email" type="email" label="Email" autocomplete="email" required :value="auth()->user()?->email" />
                <x-input name="phone" type="tel" label="Phone (optional)" autocomplete="tel" />
                <x-input name="subject" label="Subject" required />
            </div>
            <x-textarea name="message" label="Message" rows="6" required />
            @error('company') <p class="field-error">{{ $message }}</p> @enderror
            <button type="submit" class="btn btn-ink btn-lg w-full sm:w-auto" :disabled="submitting">Send message</button>
        </form>

        <aside class="space-y-4">
            @if (setting('contact_email'))
                <div class="card p-5"><p class="text-sm text-ink-500">Email</p><a href="mailto:{{ setting('contact_email') }}" class="mt-1 block font-semibold hover:text-gold-700">{{ setting('contact_email') }}</a></div>
            @endif
            @if (setting('contact_phone'))
                <div class="card p-5"><p class="text-sm text-ink-500">Phone</p><a href="tel:{{ preg_replace('/[^0-9+]/', '', setting('contact_phone')) }}" class="mt-1 block font-semibold hover:text-gold-700">{{ setting('contact_phone') }}</a></div>
            @endif
            @if (setting('contact_address'))
                <div class="card p-5"><p class="text-sm text-ink-500">Address</p><p class="mt-1 font-semibold">{!! nl2br(e(setting('contact_address'))) !!}</p></div>
            @endif
            <div class="card p-5"><p class="text-sm text-ink-500">Quick answers</p><a href="{{ route('faq') }}" class="mt-1 block font-semibold hover:text-gold-700">Read the FAQ →</a></div>
        </aside>
    </div>
</x-layouts.app>
