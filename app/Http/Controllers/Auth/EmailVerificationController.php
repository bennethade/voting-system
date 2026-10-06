<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('voter.dashboard');
        }

        return view('auth.verify-email');
    }

    /**
     * The link is a temporary signed URL (checked by the `signed`
     * middleware). It works whether or not the user is signed in on this
     * device, since people often open the email on their phone.
     */
    public function verify(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = User::findOrFail($id);

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            abort(403, 'This verification link is invalid.');
        }

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        $message = 'Your email address has been verified. You can now vote!';

        // Signed-in voters go back to the nominee they were trying to vote for, if any.
        if ($request->user()?->is($user)) {
            return redirect()->intended(route('voter.dashboard'))->with('success', $message);
        }

        return redirect()->route('login')->with('success', $message);
    }

    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('voter.dashboard');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'A new verification link has been sent to your email address.');
    }
}
