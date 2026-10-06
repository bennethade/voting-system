<?php

namespace App\Http\Controllers;

use App\Models\AwardEvent;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(): View
    {
        $events = AwardEvent::query()
            ->publiclyVisible()
            ->withCount(['categories' => fn ($q) => $q->active()])
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->paginate(12);

        return view('events.index', compact('events'));
    }

    public function show(AwardEvent $event): View
    {
        abort_unless($event->isPubliclyVisible(), 404);

        $event->load([
            'activeCategories' => fn ($q) => $q->withCount(['nominees' => fn ($n) => $n->active()]),
            'eventPackages' => fn ($q) => $q->active()->orderBy('sort_order'),
        ]);

        return view('events.show', compact('event'));
    }
}
