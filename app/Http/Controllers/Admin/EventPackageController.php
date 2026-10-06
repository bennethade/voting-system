<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\EventPackageRequest;
use App\Models\AwardEvent;
use App\Models\EventPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventPackageController extends AdminController
{
    public function index(Request $request): View
    {
        $packages = EventPackage::query()
            ->with('awardEvent:id,name,settings')
            ->when($request->integer('event'), fn ($q, $eventId) => $q->where('award_event_id', $eventId))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($this->like($request->query('q')), fn ($q, $term) => $q->where('name', 'like', $term))
            ->tap(fn ($q) => $this->applySort($q, $request, [
                'name' => 'name',
                'price' => 'price',
                'quantity_sold' => 'quantity_sold',
                'sort_order' => 'sort_order',
            ], 'sort_order', 'asc'))
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.event-packages.index', [
            'packages' => $packages,
            'events' => AwardEvent::query()->orderByDesc('id')->pluck('name', 'id'),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.event-packages.form', [
            'package' => new EventPackage([
                'award_event_id' => $request->integer('event') ?: null,
                'seats' => 10,
                'quantity_available' => 0,
                'sort_order' => 0,
                'status' => 'active',
            ]),
            'events' => AwardEvent::query()->orderByDesc('id')->pluck('name', 'id'),
        ]);
    }

    public function store(EventPackageRequest $request): RedirectResponse
    {
        $package = EventPackage::create($request->packageData());

        $this->audit->created($package, "Created table package {$package->name}");

        return redirect()
            ->route('admin.event-packages.index', ['event' => $package->award_event_id])
            ->with('success', 'Table package created.');
    }

    public function edit(EventPackage $eventPackage): View
    {
        return view('admin.event-packages.form', [
            'package' => $eventPackage,
            'events' => AwardEvent::query()->orderByDesc('id')->pluck('name', 'id'),
        ]);
    }

    public function update(EventPackageRequest $request, EventPackage $eventPackage): RedirectResponse
    {
        $eventPackage->fill($request->packageData());

        $this->audit->updated($eventPackage, "Updated table package {$eventPackage->name}");
        $eventPackage->save();

        return redirect()
            ->route('admin.event-packages.index', ['event' => $eventPackage->award_event_id])
            ->with('success', 'Table package updated.');
    }

    public function destroy(EventPackage $eventPackage): RedirectResponse
    {
        // Bookings are part of the financial record (and the FK restricts deletes).
        if ($eventPackage->bookings()->exists()) {
            return back()->with('error', 'This package has bookings and cannot be deleted. Set its status to Inactive instead.');
        }

        $this->audit->deleted($eventPackage, "Deleted table package {$eventPackage->name}");
        $eventPackage->delete();

        return back()->with('success', 'Table package deleted.');
    }
}
