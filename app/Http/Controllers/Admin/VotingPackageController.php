<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\VotingPackageRequest;
use App\Models\AwardEvent;
use App\Models\VoteOrder;
use App\Models\VotingPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VotingPackageController extends AdminController
{
    public function index(Request $request): View
    {
        $events = AwardEvent::query()->orderByDesc('id')->pluck('name', 'id');

        // Packages are always shown for one event so ordering makes sense.
        $eventId = $request->integer('event');
        $event = ($eventId ? AwardEvent::query()->find($eventId) : null)
            ?? AwardEvent::query()->orderByDesc('id')->first();

        $packages = VotingPackage::query()
            ->where('award_event_id', $event?->id ?? 0)
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($this->like($request->query('q')), fn ($q, $term) => $q->where('name', 'like', $term))
            ->tap(fn ($q) => $this->applySort($q, $request, [
                'name' => 'name',
                'amount' => 'amount',
                'votes' => 'votes',
                'sort_order' => 'sort_order',
            ], 'sort_order', 'asc'))
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.voting-packages.index', [
            'events' => $events,
            'event' => $event,
            'packages' => $packages,
            'moves' => $event ? $this->moves($event) : [],
        ]);
    }

    public function create(Request $request): View
    {
        $eventId = $request->integer('event') ?: null;

        return view('admin.voting-packages.form', [
            'package' => new VotingPackage([
                'award_event_id' => $eventId,
                'status' => 'active',
                'sort_order' => $eventId ? (int) VotingPackage::where('award_event_id', $eventId)->max('sort_order') + 1 : 0,
            ]),
            'events' => AwardEvent::query()->orderByDesc('id')->pluck('name', 'id'),
        ]);
    }

    public function store(VotingPackageRequest $request): RedirectResponse
    {
        $package = VotingPackage::create($request->packageData());

        $this->audit->created($package, "Created voting package {$package->name} ({$package->votes} votes for {$package->amount})");

        return redirect()
            ->route('admin.voting-packages.index', ['event' => $package->award_event_id])
            ->with('success', 'Voting package created.');
    }

    public function edit(VotingPackage $votingPackage): View
    {
        return view('admin.voting-packages.form', [
            'package' => $votingPackage,
            'events' => AwardEvent::query()->orderByDesc('id')->pluck('name', 'id'),
            'hasOrders' => VoteOrder::where('voting_package_id', $votingPackage->id)->exists(),
        ]);
    }

    public function update(VotingPackageRequest $request, VotingPackage $votingPackage): RedirectResponse
    {
        // Orders snapshot amount/votes at purchase time, so editing a package
        // never changes existing orders.
        $votingPackage->fill($request->packageData());

        $this->audit->updated($votingPackage, "Updated voting package {$votingPackage->name}");
        $votingPackage->save();

        return redirect()
            ->route('admin.voting-packages.index', ['event' => $votingPackage->award_event_id])
            ->with('success', 'Voting package updated.');
    }

    public function destroy(VotingPackage $votingPackage): RedirectResponse
    {
        if (VoteOrder::where('voting_package_id', $votingPackage->id)->exists()) {
            return back()->with('error', 'This package has orders and cannot be deleted. Set its status to Inactive instead.');
        }

        $this->audit->deleted($votingPackage, "Deleted voting package {$votingPackage->name}");
        $votingPackage->delete();

        return back()->with('success', 'Voting package deleted.');
    }

    /** Persist a new display order for one event's packages. */
    public function reorder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'award_event_id' => ['required', 'integer', 'exists:award_events,id'],
            'order' => ['required', 'array', 'min:1', 'max:500'],
            'order.*' => ['required', 'integer', 'distinct'],
        ]);

        $eventId = (int) $data['award_event_id'];
        $order = array_map('intval', $data['order']);

        $current = VotingPackage::query()
            ->where('award_event_id', $eventId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('sort_order', 'id');

        // Every id must belong to this event, and every package of the event must be listed.
        if (count($order) !== $current->count() || array_diff($order, $current->keys()->all()) !== []) {
            return back()->with('error', 'The package list changed while you were reordering. Please try again.');
        }

        DB::transaction(function () use ($order, $eventId) {
            foreach ($order as $position => $id) {
                VotingPackage::query()
                    ->whereKey($id)
                    ->where('award_event_id', $eventId)
                    ->update(['sort_order' => $position + 1]);
            }
        });

        $event = AwardEvent::query()->find($eventId);
        $this->audit->log(
            'voting_package.reordered',
            "Reordered voting packages for {$event->name}",
            $event,
            ['order' => $current->keys()->all()],
            ['order' => $order],
        );

        return redirect()
            ->route('admin.voting-packages.index', ['event' => $eventId])
            ->with('success', 'Package order saved.');
    }

    /**
     * For each package, the full id order that results from moving it one
     * place up or down (posted as-is to the reorder endpoint).
     *
     * @return array<int, array{up: ?array<int, int>, down: ?array<int, int>}>
     */
    private function moves(AwardEvent $event): array
    {
        $ids = VotingPackage::query()
            ->where('award_event_id', $event->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $moves = [];

        foreach ($ids as $i => $id) {
            $moves[$id] = [
                'up' => $i > 0 ? $this->swap($ids, $i, $i - 1) : null,
                'down' => $i < count($ids) - 1 ? $this->swap($ids, $i, $i + 1) : null,
            ];
        }

        return $moves;
    }

    /** @param  array<int, int>  $ids */
    private function swap(array $ids, int $a, int $b): array
    {
        [$ids[$a], $ids[$b]] = [$ids[$b], $ids[$a]];

        return $ids;
    }
}
