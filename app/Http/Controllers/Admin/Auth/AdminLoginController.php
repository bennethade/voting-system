<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuditLogger;
use App\Services\AuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminLoginController extends Controller
{
    public function __construct(
        private readonly AuthenticationService $auth,
        private readonly AuditLogger $audit,
    ) {}

    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $user = $this->auth->login(
            $request,
            $request->validated('email'),
            $request->validated('password'),
            $request->boolean('remember'),
            staffOnly: true,
        );

        $this->audit->log('auth.admin_login', "{$user->name} signed in to the admin panel", $user);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->auth->logout($request);

        return redirect()->route('admin.login')->with('success', 'You have been signed out.');
    }
}
