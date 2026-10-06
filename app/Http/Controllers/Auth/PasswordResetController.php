<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/**
 * Forgot/reset password for voters and staff, using Laravel's password
 * broker for token storage and expiry. The `$area` route default
 * ('voter' | 'admin') only changes which views and redirects are used.
 */
class PasswordResetController extends Controller
{
    public function request(Request $request): View
    {
        return view($this->isAdmin($request) ? 'admin.auth.forgot-password' : 'auth.forgot-password');
    }

    public function email(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email', 'max:255']]);

        $email = strtolower(trim((string) $request->input('email')));

        // Admin reset links are only sent to staff accounts.
        if ($this->isAdmin($request) && ! User::staff()->where('email', $email)->exists()) {
            return back()->with('status', $this->genericSentMessage());
        }

        $status = Password::sendResetLink(['email' => $email]);

        if ($status === Password::RESET_THROTTLED) {
            return back()->withInput()->withErrors(['email' => 'Please wait before requesting another reset link.']);
        }

        // Same response whether or not the account exists (no user enumeration).
        return back()->with('status', $this->genericSentMessage());
    }

    public function edit(Request $request, string $token): View
    {
        return view($this->isAdmin($request) ? 'admin.auth.reset-password' : 'auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            [
                'email' => strtolower(trim((string) $request->input('email'))),
                'password' => $request->input('password'),
                'password_confirmation' => $request->input('password_confirmation'),
                'token' => $request->input('token'),
            ],
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'This password reset link is invalid or has expired.']);
        }

        return redirect()->route($this->isAdmin($request) ? 'admin.login' : 'login')
            ->with('success', 'Your password has been reset. You can now sign in.');
    }

    private function isAdmin(Request $request): bool
    {
        return $request->route('area') === 'admin';
    }

    private function genericSentMessage(): string
    {
        return 'If an account exists for that email, we have sent a password reset link.';
    }
}
