<?php

namespace App\Http\Controllers;

use App\Models\Nominee;
use App\Services\VotingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NomineeController extends Controller
{
    public function show(Request $request, Nominee $nominee, VotingService $voting): View
    {
        $nominee->load('category.awardEvent');
        $category = $nominee->category;
        $event = $category->awardEvent;

        abort_unless($nominee->status === 'active' && $category->status === 'active' && $event->isPubliclyVisible(), 404);

        $user = $request->user();

        return view('nominees.show', [
            'nominee' => $nominee,
            'category' => $category,
            'event' => $event,
            'packages' => $event->activePackages()->get(),
            'votingOpen' => $event->isVotingOpen(),
            'closedReason' => $event->votingClosedReason(),
            'showCounts' => $event->resultsArePublic(),
            'freeVoteAvailable' => $user ? $voting->hasFreeVoteAvailable($user, $category) : null,
        ]);
    }
}
