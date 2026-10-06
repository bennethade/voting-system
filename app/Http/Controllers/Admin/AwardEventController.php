<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EventStatus;
use App\Http\Requests\Admin\AwardEventRequest;
use App\Models\AwardEvent;
use App\Services\AuditLogger;
use App\Services\LeaderboardService;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AwardEventController extends AdminController
{
    public function __construct(
        AuditLogger $audit,
        private readonly MediaService $media,
        private readonly LeaderboardService $leaderboard,
    ) {
        parent::__construct($audit);
    }

    public function index(Request $request): View
    {
        $events = AwardEvent::query()
            ->withCount(['categories', 'votingPackages'])
            ->when($this->like($request->query('q')), fn ($q, $term) => $q->where('name', 'like', $term))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->tap(fn ($q) => $this->applySort($q, $request, ['name' => 'name', 'voting_end_at' => 'voting_end_at', 'event_date' => 'event_date']))
            ->paginate(20)
            ->withQueryString();

        return view('admin.events.index', compact('events'));
    }

    public function create(): View
    {
        return view('admin.events.form', ['event' => new AwardEvent([
            'status' => EventStatus::Draft,
            'results_visibility' => 'hidden',
            'settings' => config('awards.event_defaults'),
        ])]);
    }

    public function store(AwardEventRequest $request): RedirectResponse
    {
        $event = new AwardEvent($request->eventData());
        $this->storeImages($request, $event);
        $event->save();

        $this->audit->created($event, "Created award event {$event->name}");

        return redirect()->route('admin.events.index')->with('success', 'Award event created.');
    }

    public function edit(AwardEvent $event): View
    {
        return view('admin.events.form', compact('event'));
    }

    public function update(AwardEventRequest $request, AwardEvent $event): RedirectResponse
    {
        $event->fill($request->eventData());
        $this->storeImages($request, $event);

        $this->audit->updated($event, "Updated award event {$event->name}");
        $event->save();
        $this->leaderboard->forget($event);

        return redirect()->route('admin.events.index')->with('success', 'Award event updated.');
    }

    public function destroy(AwardEvent $event): RedirectResponse
    {
        // Events with any votes or orders are part of the financial record.
        if ($event->votes()->exists() || $event->voteOrders()->exists()) {
            return back()->with('error', 'This event has votes or orders and cannot be deleted. Set its status to Cancelled or Completed instead.');
        }

        $this->audit->deleted($event, "Deleted award event {$event->name}");
        $this->media->delete($event->logo);
        $this->media->delete($event->banner);
        $event->delete();

        return redirect()->route('admin.events.index')->with('success', 'Award event deleted.');
    }

    /** Open / pause / close voting, or mark the event completed. */
    public function transition(Request $request, AwardEvent $event): RedirectResponse
    {
        $request->validate(['status' => ['required', Rule::in(['voting', 'paused', 'closed', 'completed', 'upcoming'])]]);
        $status = EventStatus::from($request->input('status'));

        if ($event->isResultsLocked() && $status === EventStatus::Voting) {
            return back()->with('error', 'Results are locked. Unlock them before reopening voting.');
        }

        $old = $event->status;
        $event->forceFill(['status' => $status])->save();
        $this->leaderboard->forget($event);

        $this->audit->log('award_event.status', "Changed {$event->name} status from {$old->value} to {$status->value}", $event, ['status' => $old->value], ['status' => $status->value]);

        return back()->with('success', "Event status changed to {$status->label()}.");
    }

    /** Freeze results: no new votes; corrections only by super admins, as audited adjustments. */
    public function lockResults(Request $request, AwardEvent $event): RedirectResponse
    {
        $event->forceFill([
            'results_locked_at' => now(),
            'results_locked_by' => $request->user()->id,
            'status' => in_array($event->status, [EventStatus::Voting, EventStatus::Paused], true) ? EventStatus::Closed : $event->status,
        ])->save();

        $this->leaderboard->forget($event);
        $this->audit->log('award_event.results_locked', "Locked final results for {$event->name}", $event);

        return back()->with('success', 'Results locked. Voting is closed and vote records are frozen.');
    }

    public function unlockResults(Request $request, AwardEvent $event): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403, 'Only a super administrator can unlock results.');

        $event->forceFill(['results_locked_at' => null, 'results_locked_by' => null])->save();
        $this->audit->log('award_event.results_unlocked', "Unlocked results for {$event->name}", $event);

        return back()->with('success', 'Results unlocked.');
    }

    private function storeImages(AwardEventRequest $request, AwardEvent $event): void
    {
        foreach (['logo' => 'events/logos', 'banner' => 'events/banners'] as $field => $dir) {
            if ($request->hasFile($field)) {
                $event->{$field} = $this->media->storeImage($request->file($field), $dir, $event->getOriginal($field));
            }
        }
    }
}
