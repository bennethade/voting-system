<x-layouts.admin-auth title="Reset admin password">
    <form method="POST" action="{{ route('admin.password.email') }}" class="space-y-5" x-data="submitOnce" @submit="submit">
        @csrf
        <x-input name="email" type="email" label="Staff email" autocomplete="username" required autofocus />
        <button class="btn btn-ink btn-lg w-full" :disabled="submitting">Send reset link</button>
        <p class="text-center text-sm"><a href="{{ route('admin.login') }}" class="font-medium text-ink-600 hover:text-ink-900">Back to sign in</a></p>
    </form>
</x-layouts.admin-auth>
