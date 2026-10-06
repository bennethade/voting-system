<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VoteType;
use App\Models\AwardEvent;
use App\Models\Nominee;
use App\Models\Vote;
use App\Services\VotingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VoteController extends AdminController
{
    public function index(Request $request): View
    {
        $votes = $this->filtered($request)
            ->with([
                'nominee:id,name,slug',
                'category:id,name',
                'awardEvent:id,name',
                'user:id,name,email',
                'voteOrder:id,reference,payment_reference,amount,currency',
                'admin:id,name',
            ])
            ->tap(fn ($q) => $this->applySort($q, $request, ['votes' => 'votes', 'created_at' => 'created_at']))
            ->paginate(30)
            ->withQueryString();

        $summary = $this->filtered($request)
            ->selectRaw('COALESCE(SUM(CASE WHEN status = ? THEN votes ELSE 0 END), 0) as valid_total', [Vote::STATUS_VALID])
            ->selectRaw('COUNT(*) as entries')
            ->first();

        return view('admin.votes.index', [
            'votes' => $votes,
            'summary' => $summary,
            'events' => AwardEvent::orderByDesc('id')->get(['id', 'name']),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->filtered($request)->with(['nominee:id,name', 'category:id,name', 'awardEvent:id,name', 'user:id,email', 'voteOrder:id,reference,payment_reference']);

        return $this->csv('votes', [
            'ID', 'Date', 'Event', 'Category', 'Nominee', 'Type', 'Votes', 'Status', 'Voter email',
            'Order reference', 'Payment reference', 'IP address', 'User agent', 'Reason',
        ], function () use ($query) {
            foreach ($query->lazyById(1000) as $v) {
                yield [
                    $v->id, $v->created_at->toDateTimeString(), $v->awardEvent?->name, $v->category?->name, $v->nominee?->name,
                    $v->vote_type->value, $v->votes, $v->status, $v->user?->email, $v->voteOrder?->reference,
                    $v->voteOrder?->payment_reference, $v->ip_address, $v->user_agent, $v->reason,
                ];
            }
        });
    }

    public function adjustForm(Request $request, VotingService $voting): View
    {
        $nominees = Nominee::query()
            ->with('category:id,name,award_event_id', 'category.awardEvent:id,name,results_locked_at')
            ->orderBy('name')
            ->get(['id', 'name', 'category_id', 'total_votes']);

        return view('admin.votes.adjust', [
            'nominees' => $nominees,
            'selected' => $request->integer('nominee') ?: null,
            'recent' => Vote::where('vote_type', VoteType::AdminAdjustment->value)
                ->with(['nominee:id,name', 'admin:id,name'])
                ->latest()
                ->limit(15)
                ->get(),
        ]);
    }

    public function adjust(Request $request, VotingService $voting): RedirectResponse
    {
        $data = $request->validate([
            'nominee_id' => ['required', 'integer', 'exists:nominees,id'],
            'adjustment' => ['required', 'integer', 'not_in:0', 'between:-1000000,1000000'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ], ['adjustment.not_in' => 'The adjustment must be a non-zero number of votes.']);

        $nominee = Nominee::with('category.awardEvent')->findOrFail($data['nominee_id']);
        $vote = $voting->adjust($nominee, (int) $data['adjustment'], $data['reason'], $request->user());

        return redirect()->route('admin.votes.adjust')->with('success', sprintf(
            'Recorded %+d votes for %s (new total %s).',
            $vote->votes,
            $nominee->name,
            number_format($vote->metadata['new_total']),
        ));
    }

    public function invalidate(Request $request, Vote $vote, VotingService $voting): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);

        $voting->invalidate($vote, $request->user(), $data['reason']);

        return back()->with('success', "Vote entry #{$vote->id} invalidated and removed from the totals.");
    }

    private function filtered(Request $request): Builder
    {
        $query = Vote::query()
            ->when($request->integer('event'), fn ($q, $id) => $q->where('award_event_id', $id))
            ->when($request->query('type'), fn ($q, $type) => $q->where('vote_type', $type))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('ip'), fn ($q, $ip) => $q->where('ip_address', $ip))
            ->when($request->integer('nominee'), fn ($q, $id) => $q->where('nominee_id', $id))
            ->when($request->integer('user'), fn ($q, $id) => $q->where('user_id', $id))
            ->when($this->like($request->query('q')), function ($q, $term) {
                $q->where(function ($w) use ($term) {
                    $w->whereHas('nominee', fn ($n) => $n->where('name', 'like', $term))
                        ->orWhereHas('user', fn ($u) => $u->where('email', 'like', $term)->orWhere('name', 'like', $term))
                        ->orWhereHas('voteOrder', fn ($o) => $o->where('reference', 'like', $term)->orWhere('payment_reference', 'like', $term)->orWhere('voter_email', 'like', $term));
                });
            });

        $request->validate([
            'type' => ['nullable', Rule::enum(VoteType::class)],
            'status' => ['nullable', Rule::in([Vote::STATUS_VALID, Vote::STATUS_INVALIDATED])],
        ]);

        return $this->applyDateRange($query, $request);
    }
}
