<?php

namespace App\Http\Requests\Admin;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdministratorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('admins.manage');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
        ]);
    }

    public function rules(): array
    {
        /** @var User|null $administrator */
        $administrator = $this->route('administrator');

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($administrator?->id)],
            'phone' => ['nullable', 'string', 'max:32'],
            'role' => ['required', Rule::in(collect(Role::staff())->map->value->all())],
            'status' => ['required', Rule::in([User::STATUS_ACTIVE, User::STATUS_INACTIVE])],
            'password' => [$administrator ? 'nullable' : 'required', 'confirmed', Password::defaults()],
        ];
    }

    /** Mass-assignable profile attributes only (role/status are force-filled separately). */
    public function profileData(): array
    {
        $data = $this->safe()->only(['name', 'email', 'phone']);

        if ($this->filled('password')) {
            $data['password'] = $this->validated('password');
        }

        return $data;
    }

    public function roleValue(): Role
    {
        return Role::from($this->validated('role'));
    }

    public function statusValue(): string
    {
        return $this->validated('status');
    }
}
