<?php

namespace App\Http\Controllers\Admin;

use App\Models\AwardEvent;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Full, uncached results for staff regardless of public visibility settings. */
class LeaderboardController extends AdminController
{
    public function index(Request $request, LeaderboardService $leaderboard): View
    {
        $events = AwardEvent::orderByDesc('id')->get();
        $event = $events->firstWhere('id', $request->integer('event')) ?? AwardEvent::current() ?? $events->first();

        return view('admin.leaderboard', [
            'events' => $events,
            'event' => $event,
            'board' => $event ? $leaderboard->build($event) : collect(),
        ]);
    }
}
