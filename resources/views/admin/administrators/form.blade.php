@php
    $editing = $administrator->exists;
@endphp

<x-layouts.admin :title="$editing ? 'Edit '.$administrator->name : 'New administrator'">
    <x-admin.page-header :title="$editing ? 'Edit administrator' : 'New administrator'">
        <x-slot:actions>
            <a href="{{ route('admin.administrators.index') }}" class="btn btn-outline">Back</a>
        </x-slot:actions>
    </x-admin.page-header>

    <form method="POST" action="{{ $editing ? route('admin.administrators.update', $administrator) : route('admin.administrators.store') }}" class="grid gap-6 xl:grid-cols-3">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="space-y-6 xl:col-span-2">
            <section class="card space-y-5 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">Account</h3>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-input name="name" label="Full name" :value="$administrator->name" required class="sm:col-span-2" />
                    <x-input name="email" type="email" label="Email" :value="$administrator->email" required />
                    <x-input name="phone" label="Phone" :value="$administrator->phone" />
                </div>
            </section>

            <section class="card space-y-5 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">Password</h3>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-input name="password" type="password" label="Password" :required="! $editing" autocomplete="new-password" :hint="$editing ? 'Leave blank to keep the current password.' : null" />
                    <x-input name="password_confirmation" type="password" label="Confirm password" :required="! $editing" autocomplete="new-password" />
                </div>
            </section>
        </div>

        <div class="space-y-6">
            <section class="card space-y-5 p-5 sm:p-6">
                <h3 class="font-sans text-base font-semibold">Access</h3>
                @if ($isSelf)
                    <input type="hidden" name="role" value="{{ $administrator->role?->value }}">
                    <input type="hidden" name="status" value="active">
                    <p class="text-sm text-ink-600">Role: <strong>{{ $administrator->role?->label() }}</strong></p>
                    <p class="hint">You cannot change your own role or deactivate your own account.</p>
                @else
                    <x-select name="role" label="Role" :options="$roleOptions" :value="$administrator->role" required hint="See the role permissions table on the Administrators page." />
                    <x-select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="$administrator->status === 'active' ? 'active' : 'inactive'" required hint="Inactive staff cannot sign in." />
                @endif
            </section>

            <button class="btn btn-gold btn-lg w-full">{{ $editing ? 'Save changes' : 'Create administrator' }}</button>
        </div>
    </form>
</x-layouts.admin>
