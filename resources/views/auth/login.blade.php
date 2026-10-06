<x-layouts.app title="Sign in" :noindex="true">
    <x-auth-card title="Welcome back" subtitle="Sign in to cast your vote and track your support.">
        <form method="POST" action="{{ route('login.store') }}" class="space-y-5" x-data="submitOnce" @submit="submit">
            @csrf
            <x-input name="email" type="email" label="Email address" autocomplete="email" required autofocus />
            <div>
                <x-input name="password" type="password" label="Password" autocomplete="current-password" required />
                <div class="mt-2 text-right">
                    <a href="{{ route('password.request') }}" class="text-sm font-medium text-gold-700 hover:underline">Forgot your password?</a>
                </div>
            </div>
            <label class="flex items-center gap-2.5 text-sm text-ink-700">
                <input type="checkbox" name="remember" value="1" class="size-4 rounded border-ink-300 text-gold-500 focus:ring-gold-400" @checked(old('remember'))>
                Keep me signed in
            </label>
            <button type="submit" class="btn btn-ink btn-lg w-full" :disabled="submitting">Sign in</button>
        </form>
        <x-slot:footer>
            New here? <a href="{{ route('register') }}" class="font-semibold text-gold-300 hover:underline">Create a free account</a>
        </x-slot:footer>
    </x-auth-card>
</x-layouts.app>
