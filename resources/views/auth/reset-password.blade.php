<x-layouts.app title="Choose a new password" :noindex="true">
    <x-auth-card title="Choose a new password">
        <form method="POST" action="{{ route('password.update') }}" class="space-y-5" x-data="submitOnce" @submit="submit">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <x-input name="email" type="email" label="Email address" :value="$email" autocomplete="email" required />
            <x-input name="password" type="password" label="New password" autocomplete="new-password" required autofocus />
            <x-input name="password_confirmation" type="password" label="Confirm new password" autocomplete="new-password" required />
            <button type="submit" class="btn btn-ink btn-lg w-full" :disabled="submitting">Reset password</button>
        </form>
    </x-auth-card>
</x-layouts.app>
