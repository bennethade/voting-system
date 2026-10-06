<x-layouts.voter title="Profile">
    <div class="grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('voter.profile.update') }}" class="card space-y-5 p-5 sm:p-6">
            @csrf
            @method('PUT')
            <h2 class="font-sans text-lg font-semibold">Personal details</h2>
            <x-input name="name" label="Full name" :value="$user->name" required autocomplete="name" />
            <x-input name="email" type="email" label="Email" :value="$user->email" required autocomplete="email" hint="Changing your email requires verifying the new address." />
            <x-input name="phone" type="tel" label="Phone" :value="$user->phone" autocomplete="tel" />
            <button class="btn btn-ink">Save changes</button>
        </form>

        <form method="POST" action="{{ route('voter.profile.password') }}" class="card space-y-5 p-5 sm:p-6">
            @csrf
            @method('PUT')
            <h2 class="font-sans text-lg font-semibold">Change password</h2>
            @foreach (['current_password' => 'Current password', 'password' => 'New password', 'password_confirmation' => 'Confirm new password'] as $field => $label)
                <div>
                    <label for="{{ $field }}" class="label">{{ $label }}</label>
                    <input id="{{ $field }}" name="{{ $field }}" type="password" required autocomplete="{{ $field === 'current_password' ? 'current-password' : 'new-password' }}" @class(['input', 'input-error' => $errors->password->has($field)])>
                    @if ($errors->password->has($field))<p class="field-error">{{ $errors->password->first($field) }}</p>@endif
                </div>
            @endforeach
            <button class="btn btn-ink">Update password</button>
        </form>
    </div>
    <p class="mt-6 text-sm text-ink-500">Member since {{ $user->created_at->format('F Y') }} · Last sign-in {{ $user->last_login_at?->diffForHumans() ?? '—' }}</p>
</x-layouts.voter>
