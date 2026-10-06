<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\VoteType;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VoterController extends AdminController
{
    public const STATUSES = [User::STATUS_ACTIVE, User::STATUS_INACTIVE, User::STATUS_SUSPENDED];

    public function index(Request $request): View
    {
        $voters = User::query()
            ->voters()
            ->withCount('votes')
            ->withSum(['votes as valid_votes' => fn ($q) => $q->where('status', Vote::STATUS_VALID)], 'votes')
            ->when($this->like($request->query('q')), fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('name', 'like', $term)
                ->orWhere('email', 'like', $term)
                ->orWhere('phone', 'like', $term)))
            ->when(in_array($request->query('status'), self::STATUSES, true), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->query('verified') === 'yes', fn ($q) => $q->whereNotNull('email_verified_at'))
            ->when($request->query('verified') === 'no', fn ($q) => $q->whereNull('email_verified_at'))
            ->tap(fn ($q) => $this->applyDateRange($q, $request))
            ->tap(fn ($q) => $this->applySort($q, $request, ['name' => 'name', 'created_at' => 'created_at', 'last_login_at' => 'last_login_at']))
            ->paginate(25)
            ->withQueryString();

        return view('admin.voters.index', compact('voters'));
    }

    public function show(User $voter): View
    {
        abort_if($voter->isStaff(), 404);

        $voteTotals = $voter->votes()
            ->where('status', Vote::STATUS_VALID)
            ->selectRaw('vote_type, SUM(votes) as total')
            ->groupBy('vote_type')
            ->pluck('total', 'vote_type');

        $stats = [
            'free' => (int) ($voteTotals[VoteType::Free->value] ?? 0),
            'paid' => (int) ($voteTotals[VoteType::Paid->value] ?? 0),
            'total' => (int) $voteTotals->sum(),
            'spent' => (string) $voter->voteOrders()->where('status', OrderStatus::Paid->value)->sum('amount'),
        ];

        $recentVotes = $voter->votes()->with(['nominee:id,name', 'category:id,name'])->latest()->limit(15)->get();
        $recentOrders = $voter->voteOrders()->with('nominee:id,name')->latest()->limit(10)->get();
        $recentPayments = $voter->payments()->latest()->limit(10)->get();

        $ips = $voter->votes()
            ->whereNotNull('ip_address')
            ->selectRaw('ip_address, COUNT(*) as uses, MAX(created_at) as last_used_at')
            ->groupBy('ip_address')
            ->orderByDesc('uses')
            ->limit(20)
            ->toBase()
            ->get();

        return view('admin.voters.show', compact('voter', 'stats', 'recentVotes', 'recentOrders', 'recentPayments', 'ips'));
    }

    public function updateStatus(Request $request, User $voter): RedirectResponse
    {
        abort_if($voter->isStaff(), 404);

        $data = $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $old = $voter->status;

        if ($old === $data['status']) {
            return back()->with('error', 'The account already has that status.');
        }

        $voter->forceFill(['status' => $data['status']])->save();

        $this->audit->log(
            'user.status',
            "Changed status of voter {$voter->email} from {$old} to {$data['status']}",
            $voter,
            ['status' => $old],
            ['status' => $data['status'], 'reason' => $data['reason']],
        );

        return back()->with('success', 'Voter status changed to '.ucfirst($data['status']).'.');
    }
}
