<x-layouts.app title="Verify your email" :noindex="true">
    <x-auth-card title="Check your inbox" subtitle="One more step before you can cast your free vote.">
        <div class="space-y-5 text-sm text-ink-700">
            <div class="flex items-start gap-3 rounded-xl bg-gold-50 p-4 text-gold-900">
                <svg class="size-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.24a2.25 2.25 0 01-1.07 1.92l-7.5 4.61a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.91V6.75"/></svg>
                <p>We sent a verification link to <strong>{{ auth()->user()->email }}</strong>. Click it to activate voting on your account. The link expires in 60 minutes.</p>
            </div>
            <p>Didn't get it? Check your spam folder, or request a new link.</p>
            <form method="POST" action="{{ route('verification.send') }}" x-data="submitOnce" @submit="submit">
                @csrf
                <button type="submit" class="btn btn-gold w-full" :disabled="submitting">Resend verification email</button>
            </form>
            <div class="flex items-center justify-between border-t border-ink-100 pt-4">
                <a href="{{ route('voter.profile') }}" class="font-medium text-ink-600 hover:text-ink-900">Wrong email? Update it</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="font-medium text-ink-600 hover:text-ink-900">Sign out</button>
                </form>
            </div>
        </div>
    </x-auth-card>
</x-layouts.app>
