<x-layouts.app title="Forgot password" :noindex="true">
    <x-auth-card title="Reset your password" subtitle="Enter your email and we'll send you a secure reset link.">
        <form method="POST" action="{{ route('password.email') }}" class="space-y-5" x-data="submitOnce" @submit="submit">
            @csrf
            <x-input name="email" type="email" label="Email address" autocomplete="email" required autofocus />
            <button type="submit" class="btn btn-ink btn-lg w-full" :disabled="submitting">Email me a reset link</button>
        </form>
        <x-slot:footer>
            Remembered it? <a href="{{ route('login') }}" class="font-semibold text-gold-300 hover:underline">Back to sign in</a>
        </x-slot:footer>
    </x-auth-card>
</x-layouts.app>
