<x-layouts.admin-auth title="Sign in to the admin panel">
    <form method="POST" action="{{ route('admin.login.store') }}" class="space-y-5" x-data="submitOnce" @submit="submit">
        @csrf
        <x-input name="email" type="email" label="Email" autocomplete="username" required autofocus />
        <x-input name="password" type="password" label="Password" autocomplete="current-password" required />
        <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2 text-ink-700"><input type="checkbox" name="remember" value="1" class="size-4 rounded border-ink-300"> Remember me</label>
            <a href="{{ route('admin.password.request') }}" class="font-medium text-gold-700 hover:underline">Forgot password?</a>
        </div>
        <button class="btn btn-ink btn-lg w-full" :disabled="submitting">Sign in</button>
    </form>
</x-layouts.admin-auth>
