<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FraudStatus;
use App\Models\AwardEvent;
use App\Models\FraudFlag;
use App\Models\Payment;
use App\Models\Vote;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends AdminController
{
    public function index(Request $request, DashboardService $dashboard): View
    {
        $events = AwardEvent::orderByDesc('id')->get(['id', 'name']);
        $event = $request->integer('event') ? $events->firstWhere('id', $request->integer('event')) : null;
        $user = $request->user();

        $data = $dashboard->stats($event);

        return view('admin.dashboard', [
            'events' => $events,
            'event' => $event,
            'totals' => $data['totals'],
            'charts' => $data['charts'],
            'recentVotes' => $user->can('votes.view')
                ? Vote::with(['nominee:id,name', 'category:id,name', 'user:id,name'])->latest()->limit(8)->get()
                : collect(),
            'recentPayments' => $user->can('payments.view')
                ? Payment::latest()->limit(8)->get()
                : collect(),
            'openFlags' => $user->can('fraud.manage')
                ? FraudFlag::where('status', FraudStatus::Open->value)->count()
                : null,
            'canSeeMoney' => $user->can('payments.view'),
        ]);
    }
}
