<x-layouts.admin-auth title="Choose a new password">
    <form method="POST" action="{{ route('admin.password.update') }}" class="space-y-5" x-data="submitOnce" @submit="submit">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-input name="email" type="email" label="Email" :value="$email" autocomplete="username" required />
        <x-input name="password" type="password" label="New password" autocomplete="new-password" required autofocus />
        <x-input name="password_confirmation" type="password" label="Confirm password" autocomplete="new-password" required />
        <button class="btn btn-ink btn-lg w-full" :disabled="submitting">Reset password</button>
    </form>
</x-layouts.admin-auth>
