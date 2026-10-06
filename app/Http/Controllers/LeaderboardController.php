<?php

namespace App\Http\Controllers;

use App\Models\AwardEvent;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaderboardController extends Controller
{
    public function index(Request $request, LeaderboardService $leaderboard): View
    {
        $event = AwardEvent::fromRequestOrCurrent($request->query('event'));
        $events = AwardEvent::publiclyVisible()->orderByDesc('id')->get(['id', 'name', 'slug']);

        // Results are only computed (and sent to the browser) when the event allows it.
        $visible = $event?->resultsArePublic() ?? false;

        return view('leaderboard', [
            'event' => $event,
            'events' => $events,
            'visible' => $visible,
            'board' => $visible ? $leaderboard->forEvent($event) : collect(),
        ]);
    }
}
