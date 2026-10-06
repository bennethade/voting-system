<?php

namespace App\Http\Controllers\Voter;

use App\Enums\OrderStatus;
use App\Enums\VoteType;
use App\Http\Controllers\Controller;
use App\Models\AwardEvent;
use App\Models\Payment;
use App\Models\Vote;
use App\Models\VoteOrder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $stats = Vote::query()
            ->where('user_id', $user->id)
            ->valid()
            ->selectRaw('COALESCE(SUM(votes), 0) as total')
            ->selectRaw('COALESCE(SUM(CASE WHEN vote_type = ? THEN votes ELSE 0 END), 0) as free', [VoteType::Free->value])
            ->selectRaw('COALESCE(SUM(CASE WHEN vote_type = ? THEN votes ELSE 0 END), 0) as paid', [VoteType::Paid->value])
            ->first();

        $spent = VoteOrder::where('user_id', $user->id)->where('status', OrderStatus::Paid->value)->sum('amount');

        $recentVotes = Vote::where('user_id', $user->id)
            ->with(['nominee:id,name,slug,image', 'category:id,name'])
            ->latest()
            ->limit(5)
            ->get();

        $recentOrders = VoteOrder::where('user_id', $user->id)
            ->with('nominee:id,name,slug')
            ->latest()
            ->limit(5)
            ->get();

        $event = AwardEvent::current();

        return view('voter.dashboard', compact('user', 'stats', 'spent', 'recentVotes', 'recentOrders', 'event'));
    }

    public function votes(Request $request): View
    {
        $votes = Vote::where('user_id', $request->user()->id)
            ->with(['nominee:id,name,slug,image', 'category:id,name', 'awardEvent:id,name', 'voteOrder:id,reference'])
            ->latest()
            ->paginate(20);

        return view('voter.votes', compact('votes'));
    }

    public function orders(Request $request): View
    {
        $orders = VoteOrder::where('user_id', $request->user()->id)
            ->with(['nominee:id,name,slug', 'category:id,name'])
            ->latest()
            ->paginate(20);

        return view('voter.orders', compact('orders'));
    }

    public function payments(Request $request): View
    {
        $payments = Payment::where('user_id', $request->user()->id)
            ->with('payable')
            ->latest()
            ->paginate(20);

        return view('voter.payments', compact('payments'));
    }
}
