<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Session authentication for voters and staff, built directly on Laravel's
 * session guard (no starter kit). Throttling is applied at the route level.
 */
class AuthenticationService
{
    /**
     * @param  bool  $staffOnly  reject non-staff accounts (admin login)
     *
     * @throws ValidationException
     */
    public function login(Request $request, string $email, string $password, bool $remember, bool $staffOnly = false): User
    {
        $credentials = ['email' => strtolower(trim($email)), 'password' => $password];

        // Validate credentials without logging in, so status/role checks
        // happen before a session is ever established.
        if (! Auth::validate($credentials)) {
            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }

        /** @var User $user */
        $user = User::where('email', $credentials['email'])->firstOrFail();

        if ($staffOnly && ! $user->isStaff()) {
            // Same message as bad credentials: don't reveal which accounts are staff.
            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }

        if (! $user->isActive()) {
            throw ValidationException::withMessages(['email' => 'This account has been deactivated. Please contact support.']);
        }

        Auth::login($user, $remember);
        $request->session()->regenerate();

        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->saveQuietly();

        return $user;
    }

    public function logout(Request $request): void
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    /** @param array{name: string, email: string, phone?: ?string, password: string} $data */
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => strtolower(trim($data['email'])),
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'], // hashed by the model cast
            ]);

            $user->forceFill(['role' => Role::Voter, 'status' => User::STATUS_ACTIVE])->save();

            return $user;
        });
    }
}
