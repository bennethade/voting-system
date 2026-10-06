<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Models\AwardEvent;
use App\Models\EventBooking;
use App\Models\EventPackage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventBookingController extends AdminController
{
    public function index(Request $request): View
    {
        $paid = $this->filtered($request)
            ->where('status', OrderStatus::Paid->value)
            ->selectRaw('COUNT(*) as bookings, COALESCE(SUM(amount), 0) as revenue, COALESCE(SUM(seats), 0) as seats')
            ->toBase()
            ->first();

        $bookings = $this->filtered($request)
            ->with(['awardEvent:id,name', 'eventPackage:id,name'])
            ->tap(fn ($q) => $this->applySort($q, $request, ['amount' => 'amount', 'quantity' => 'quantity', 'created_at' => 'created_at']))
            ->paginate(25)
            ->withQueryString();

        $events = AwardEvent::orderBy('name')->pluck('name', 'id');
        $packages = EventPackage::query()
            ->with('awardEvent:id,name')
            ->when($request->integer('event'), fn ($q, $event) => $q->where('award_event_id', $event))
            ->orderBy('award_event_id')
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn (EventPackage $p) => [$p->id => $p->name.' ('.($p->awardEvent?->name ?? '—').')']);

        return view('admin.bookings.index', compact('bookings', 'paid', 'events', 'packages'));
    }

    public function show(EventBooking $eventBooking): View
    {
        $eventBooking->load(['awardEvent:id,name', 'eventPackage:id,name,seats', 'user:id,name,email,role']);
        $payments = $eventBooking->payments()->latest()->get();

        return view('admin.bookings.show', [
            'booking' => $eventBooking,
            'payments' => $payments,
            'attendees' => collect($eventBooking->attendees ?? [])->filter(fn ($a) => is_array($a))->values(),
            'oversold' => (bool) data_get($eventBooking->metadata, 'oversold', false),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->filtered($request)->with(['awardEvent:id,name', 'eventPackage:id,name']);

        return $this->csv('event-bookings', [
            'Reference', 'Date', 'Event', 'Package', 'Contact name', 'Email', 'Phone', 'Organization',
            'Quantity', 'Seats', 'Unit price', 'Amount', 'Currency', 'Status', 'Payment reference', 'Paid at', 'Attendees',
        ], function () use ($query) {
            foreach ($query->lazyById(1000) as $b) {
                yield [
                    $b->reference,
                    $b->created_at?->format('Y-m-d H:i:s'),
                    $b->awardEvent?->name,
                    $b->eventPackage?->name,
                    $b->contact_name,
                    $b->contact_email,
                    $b->contact_phone,
                    $b->organization,
                    $b->quantity,
                    $b->seats,
                    $b->unit_price,
                    $b->amount,
                    $b->currency,
                    $b->status,
                    $b->payment_reference,
                    $b->paid_at?->format('Y-m-d H:i:s'),
                    collect($b->attendees ?? [])->pluck('name')->filter()->implode('; '),
                ];
            }
        });
    }

    /** Base query with the listing filters applied (shared by list, totals and export). */
    private function filtered(Request $request): Builder
    {
        $statuses = collect(OrderStatus::cases())->map->value->all();

        return EventBooking::query()
            ->when($this->like($request->query('q')), fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('reference', 'like', $term)
                ->orWhere('contact_name', 'like', $term)
                ->orWhere('contact_email', 'like', $term)
                ->orWhere('contact_phone', 'like', $term)
                ->orWhere('organization', 'like', $term)))
            ->when(in_array($request->query('status'), $statuses, true), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->integer('event'), fn ($q, $event) => $q->where('award_event_id', $event))
            ->when($request->integer('package'), fn ($q, $package) => $q->where('event_package_id', $package))
            ->tap(fn ($q) => $this->applyDateRange($q, $request));
    }
}
