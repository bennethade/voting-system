<?php

namespace App\Http\Controllers;

use App\Models\Nominee;
use App\Services\VotingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FreeVoteController extends Controller
{
    /**
     * Only the nominee comes from the request. Who is voting comes from the
     * session; whether they may vote is decided by VotingService.
     * VotingException is rendered as a flash/JSON error (bootstrap/app.php).
     */
    public function store(Request $request, Nominee $nominee, VotingService $voting): RedirectResponse|JsonResponse
    {
        $voting->castFreeVote($request->user(), $nominee, $request->ip(), $request->userAgent());

        $message = "Your free vote for {$nominee->name} has been recorded. Thank you!";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('success', $message);
    }
}
