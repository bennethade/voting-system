<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Requests\Admin\AdministratorRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdministratorController extends AdminController
{
    public function index(Request $request): View
    {
        $staffRoles = collect(Role::staff())->map->value->all();

        $administrators = User::query()
            ->staff()
            ->when($this->like($request->query('q')), fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('name', 'like', $term)
                ->orWhere('email', 'like', $term)))
            ->when(in_array($request->query('role'), $staffRoles, true), fn ($q) => $q->where('role', $request->query('role')))
            ->when(in_array($request->query('status'), [User::STATUS_ACTIVE, User::STATUS_INACTIVE, User::STATUS_SUSPENDED], true), fn ($q) => $q->where('status', $request->query('status')))
            ->tap(fn ($q) => $this->applySort($q, $request, ['name' => 'name', 'created_at' => 'created_at', 'last_login_at' => 'last_login_at'], 'name', 'asc'))
            ->paginate(20)
            ->withQueryString();

        return view('admin.administrators.index', [
            'administrators' => $administrators,
            'roleOptions' => $this->roleOptions(),
            'roleMatrix' => $this->roleMatrix(),
        ]);
    }

    public function create(Request $request): View
    {
        $administrator = new User;
        $administrator->forceFill(['role' => Role::Admin, 'status' => User::STATUS_ACTIVE]);

        return view('admin.administrators.form', [
            'administrator' => $administrator,
            'roleOptions' => $this->assignableRoleOptions($request->user()),
            'isSelf' => false,
        ]);
    }

    public function store(AdministratorRequest $request): RedirectResponse
    {
        $role = $request->roleValue();

        if ($role === Role::SuperAdmin && ! $request->user()->isSuperAdmin()) {
            return back()->withInput()->with('error', 'Only a super administrator can grant the Super Administrator role.');
        }

        $administrator = User::create($request->profileData());
        $administrator->forceFill([
            'role' => $role,
            'status' => $request->statusValue(),
            'email_verified_at' => now(),
        ])->save();

        $this->audit->created($administrator, "Created administrator {$administrator->email} with role {$role->label()}");

        return redirect()->route('admin.administrators.index')->with('success', 'Administrator account created.');
    }

    public function edit(Request $request, User $administrator): View
    {
        abort_unless($administrator->isStaff(), 404);

        return view('admin.administrators.form', [
            'administrator' => $administrator,
            'roleOptions' => $this->assignableRoleOptions($request->user(), $administrator),
            'isSelf' => $request->user()->is($administrator),
        ]);
    }

    public function update(AdministratorRequest $request, User $administrator): RedirectResponse
    {
        abort_unless($administrator->isStaff(), 404);

        $actor = $request->user();
        $oldRole = $administrator->role;
        $newRole = $request->roleValue();
        $newStatus = $request->statusValue();

        if ($error = $this->guardChange($actor, $administrator, $newRole, $newStatus)) {
            return back()->withInput()->with('error', $error);
        }

        $administrator->fill($request->profileData());
        $administrator->forceFill(['role' => $newRole, 'status' => $newStatus]);

        $this->audit->updated($administrator, "Updated administrator {$administrator->email}");
        $administrator->save();

        if ($oldRole !== $newRole) {
            $this->audit->log(
                'user.role_changed',
                "Changed role of {$administrator->email} from {$oldRole?->label()} to {$newRole->label()}",
                $administrator,
                ['role' => $oldRole?->value],
                ['role' => $newRole->value],
            );
        }

        return redirect()->route('admin.administrators.index')->with('success', 'Administrator updated.');
    }

    public function destroy(Request $request, User $administrator): RedirectResponse
    {
        abort_unless($administrator->isStaff(), 404);

        $actor = $request->user();

        if ($actor->is($administrator)) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($administrator->isSuperAdmin() && ! $actor->isSuperAdmin()) {
            return back()->with('error', 'Only a super administrator can delete another super administrator.');
        }

        if ($this->isLastActiveSuperAdmin($administrator)) {
            return back()->with('error', 'This is the last active super administrator and cannot be deleted.');
        }

        // Accounts that have acted on the platform are part of the audit trail.
        if (AuditLog::where('user_id', $administrator->id)->exists()) {
            return back()->with('error', 'This account has audit history and cannot be deleted. Set its status to Inactive instead.');
        }

        $this->audit->deleted($administrator, "Deleted administrator {$administrator->email}");
        $administrator->delete();

        return redirect()->route('admin.administrators.index')->with('success', 'Administrator deleted.');
    }

    /** Returns an error message if the requested role/status change is not allowed. */
    private function guardChange(User $actor, User $target, Role $newRole, string $newStatus): ?string
    {
        $roleChanging = $target->role !== $newRole;

        if ($actor->is($target)) {
            if ($roleChanging) {
                return 'You cannot change your own role.';
            }

            if ($newStatus !== User::STATUS_ACTIVE) {
                return 'You cannot deactivate your own account.';
            }
        }

        if (($newRole === Role::SuperAdmin || $target->isSuperAdmin()) && $roleChanging && ! $actor->isSuperAdmin()) {
            return 'Only a super administrator can grant or remove the Super Administrator role.';
        }

        $losesSuperAdmin = $newRole !== Role::SuperAdmin || $newStatus !== User::STATUS_ACTIVE;

        if ($losesSuperAdmin && $this->isLastActiveSuperAdmin($target)) {
            return 'This is the last active super administrator. Promote another account first.';
        }

        return null;
    }

    private function isLastActiveSuperAdmin(User $user): bool
    {
        if (! $user->isSuperAdmin() || ! $user->isActive()) {
            return false;
        }

        return ! User::query()
            ->where('role', Role::SuperAdmin->value)
            ->where('status', User::STATUS_ACTIVE)
            ->whereKeyNot($user->id)
            ->exists();
    }

    /** @return array<string, string> */
    private function roleOptions(): array
    {
        return collect(Role::staff())->mapWithKeys(fn (Role $r) => [$r->value => $r->label()])->all();
    }

    /** Super admin can only be granted by a super admin (or kept on an existing super admin). */
    private function assignableRoleOptions(User $actor, ?User $target = null): array
    {
        return collect(Role::staff())
            ->filter(fn (Role $r) => $r !== Role::SuperAdmin || $actor->isSuperAdmin() || $target?->isSuperAdmin())
            ->mapWithKeys(fn (Role $r) => [$r->value => $r->label()])
            ->all();
    }

    /**
     * What each staff role may do, for the reference table on the index page.
     *
     * @return array<int, array{label: string, permissions: array<int, string>, all: bool}>
     */
    private function roleMatrix(): array
    {
        $labels = config('awards.permissions', []);

        return collect(Role::staff())->map(function (Role $role) use ($labels) {
            $granted = config('awards.roles.'.$role->value, []);
            $all = in_array('*', $granted, true);

            return [
                'label' => $role->label(),
                'all' => $all,
                'permissions' => $all ? [] : array_values(array_map(fn ($p) => $labels[$p] ?? $p, $granted)),
            ];
        })->all();
    }
}
