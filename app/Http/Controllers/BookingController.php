<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Requests\BookingRequest;
use App\Models\AwardEvent;
use App\Models\EventBooking;
use App\Models\EventPackage;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $events = AwardEvent::publiclyVisible()
            ->whereHas('eventPackages', fn ($q) => $q->active())
            ->orderByDesc('event_date')
            ->get();

        $event = $events->firstWhere('slug', $request->query('event')) ?? $events->first();

        $packages = $event
            ? $event->eventPackages()->active()->orderBy('sort_order')->orderByDesc('price')->get()
            : collect();

        return view('bookings.index', compact('events', 'event', 'packages'));
    }

    public function create(EventPackage $eventPackage): View
    {
        $eventPackage->load('awardEvent');
        abort_unless($eventPackage->status === 'active' && $eventPackage->awardEvent->isPubliclyVisible(), 404);

        return view('bookings.create', ['package' => $eventPackage, 'event' => $eventPackage->awardEvent]);
    }

    public function store(BookingRequest $request, EventPackage $eventPackage, BookingService $bookings): RedirectResponse
    {
        $booking = $bookings->createBooking(
            $eventPackage,
            (int) $request->validated('quantity'),
            $request->safe()->only(['name', 'email', 'phone', 'organization', 'notes']),
            (array) $request->validated('attendees', []),
            $request->user(),
            $request->ip(),
        );

        return redirect(URL::temporarySignedRoute('bookings.review', now()->addHours(2), ['eventBooking' => $booking->reference]));
    }

    public function show(EventBooking $eventBooking): View|RedirectResponse
    {
        if ($eventBooking->status === OrderStatus::Paid) {
            return redirect(ReceiptController::bookingUrl($eventBooking));
        }

        $eventBooking->load('eventPackage', 'awardEvent');

        return view('bookings.review', [
            'booking' => $eventBooking,
            'payUrl' => URL::temporarySignedRoute('checkout.pay', now()->addHours(2), [
                'type' => 'event_booking',
                'reference' => $eventBooking->reference,
            ]),
        ]);
    }
}
