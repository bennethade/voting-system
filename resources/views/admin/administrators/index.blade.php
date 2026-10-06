<x-layouts.admin title="Administrators">
    <x-admin.page-header title="Administrators" subtitle="Staff accounts and what each role is allowed to do.">
        <x-slot:actions>
            <a href="{{ route('admin.administrators.create') }}" class="btn btn-gold">New administrator</a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.filters placeholder="Name or email…">
        <x-select name="role" label="Role" :options="$roleOptions" :value="request('role')" placeholder="All roles" class="[&_label]:text-xs" />
        <x-select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended']" :value="request('status')" placeholder="All statuses" class="[&_label]:text-xs" />
    </x-admin.filters>

    <div class="card overflow-hidden">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th><x-admin.sort-link column="name" label="Name" /></th>
                        <th>Role</th>
                        <th>Status</th>
                        <th><x-admin.sort-link column="last_login_at" label="Last sign-in" /></th>
                        <th><x-admin.sort-link column="created_at" label="Created" /></th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($administrators as $administrator)
                        <tr>
                            <td>
                                <a href="{{ route('admin.administrators.edit', $administrator) }}" class="font-semibold text-ink-900 hover:text-gold-700">{{ $administrator->name }}</a>
                                @if (auth()->user()->is($administrator))<span class="badge ml-1 bg-gold-100 text-gold-800">You</span>@endif
                                <span class="block text-xs text-ink-400">{{ $administrator->email }}</span>
                            </td>
                            <td class="text-sm">{{ $administrator->role?->label() }}</td>
                            <td><x-status-badge :status="$administrator->status" /></td>
                            <td class="text-xs whitespace-nowrap">{{ $administrator->last_login_at?->format('j M Y, g:ia') ?? 'Never' }}</td>
                            <td class="text-xs whitespace-nowrap">{{ $administrator->created_at?->format('j M Y, g:ia') }}</td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('admin.administrators.edit', $administrator) }}" class="btn btn-outline btn-sm">Edit</a>
                                @unless (auth()->user()->is($administrator))
                                    <x-admin.confirm-form :action="route('admin.administrators.destroy', $administrator)" method="DELETE" :confirm="'Delete '.$administrator->name.'? Accounts with audit history cannot be deleted.'" class="ml-2 text-rose-600 hover:text-rose-800" />
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty-state title="No administrators found" class="border-0" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $administrators->links() }}</div>

    <section class="mt-8">
        <h3 class="mb-3 font-sans text-base font-semibold">Role permissions</h3>
        <div class="card overflow-hidden">
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th class="w-56">Role</th><th>Can do</th></tr></thead>
                    <tbody>
                        @foreach ($roleMatrix as $role)
                            <tr>
                                <td class="font-medium align-top">{{ $role['label'] }}</td>
                                <td>
                                    @if ($role['all'])
                                        <span class="badge bg-ink-900 text-white">Everything, including managing administrators and settings</span>
                                    @else
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach ($role['permissions'] as $permission)
                                                <span class="badge bg-ink-50 text-ink-700">{{ $permission }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</x-layouts.admin>
