<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(private readonly AuthenticationService $auth) {}

    public function create(Request $request): View
    {
        $this->rememberReturnPath($request);

        return view('auth.login');
    }

    /**
     * "Sign in to vote" links carry ?return=/path so voters land back on the
     * nominee they picked. Only same-site relative paths are accepted, to
     * avoid an open redirect.
     */
    public static function rememberReturnPath(Request $request): void
    {
        $return = (string) $request->query('return');

        if ($return !== '' && str_starts_with($return, '/') && ! str_starts_with($return, '//') && ! str_contains($return, '\\')) {
            $request->session()->put('url.intended', url($return));
        }
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $user = $this->auth->login(
            $request,
            $request->validated('email'),
            $request->validated('password'),
            $request->boolean('remember'),
        );

        $home = $user->isStaff() ? route('admin.dashboard') : route('voter.dashboard');

        return redirect()->intended($home)->with('success', 'Welcome back, '.$user->name.'!');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->auth->logout($request);

        return redirect()->route('home')->with('success', 'You have been signed out.');
    }
}
