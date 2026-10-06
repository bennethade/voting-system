<?php

namespace App\Http\Controllers;

use App\Models\AwardEvent;
use App\Models\Category;
use App\Services\VotingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Category → nominees → free or paid vote. */
class VotingController extends Controller
{
    public function index(): RedirectResponse|View
    {
        $event = AwardEvent::current();

        if ($event) {
            return redirect()->route('voting.event', $event);
        }

        return view('voting.none');
    }

    public function event(AwardEvent $event): View
    {
        abort_unless($event->isPubliclyVisible(), 404);

        $categories = $event->activeCategories()
            ->withCount(['nominees' => fn ($q) => $q->active()])
            ->get();

        return view('voting.event', compact('event', 'categories'));
    }

    public function category(Request $request, AwardEvent $event, Category $category, VotingService $voting): View
    {
        abort_unless($event->isPubliclyVisible() && $category->status === 'active', 404);

        $category->setRelation('awardEvent', $event);
        $nominees = $category->activeNominees()->get();
        $packages = $event->activePackages()->get();
        $otherCategories = $event->activeCategories()->where('id', '!=', $category->id)->get(['id', 'name', 'slug', 'award_event_id']);

        $user = $request->user();

        return view('voting.category', [
            'event' => $event,
            'category' => $category,
            'nominees' => $nominees,
            'packages' => $packages,
            'otherCategories' => $otherCategories,
            'votingOpen' => $event->isVotingOpen(),
            'closedReason' => $event->votingClosedReason(),
            'showCounts' => $event->resultsArePublic(),
            'freeVoteAvailable' => $user ? $voting->hasFreeVoteAvailable($user, $category) : null,
        ]);
    }
}
