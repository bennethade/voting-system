<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Models\AwardEvent;
use App\Models\VoteOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VoteOrderController extends AdminController
{
    public function index(Request $request): View
    {
        $orders = $this->filtered($request)
            ->with(['nominee:id,name', 'category:id,name', 'awardEvent:id,name'])
            ->tap(fn ($q) => $this->applySort($q, $request, ['amount' => 'amount', 'votes' => 'votes', 'created_at' => 'created_at', 'paid_at' => 'paid_at']))
            ->paginate(30)
            ->withQueryString();

        $totals = $this->filtered($request)
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('COALESCE(SUM(CASE WHEN status = ? THEN amount ELSE 0 END), 0) as revenue', [OrderStatus::Paid->value])
            ->selectRaw('COALESCE(SUM(CASE WHEN status = ? THEN votes ELSE 0 END), 0) as votes', [OrderStatus::Paid->value])
            ->first();

        return view('admin.orders.index', [
            'orders' => $orders,
            'totals' => $totals,
            'events' => AwardEvent::orderByDesc('id')->get(['id', 'name']),
        ]);
    }

    public function show(VoteOrder $voteOrder): View
    {
        $voteOrder->load(['nominee', 'category', 'awardEvent', 'votingPackage', 'user', 'vote.invalidator', 'payments' => fn ($q) => $q->latest()]);

        return view('admin.orders.show', ['order' => $voteOrder]);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->filtered($request)->with(['nominee:id,name', 'category:id,name', 'awardEvent:id,name']);

        return $this->csv('vote-orders', [
            'Reference', 'Created', 'Paid at', 'Event', 'Category', 'Nominee', 'Votes', 'Amount', 'Currency',
            'Status', 'Voter name', 'Voter email', 'Voter phone', 'Payment reference', 'IP address',
        ], function () use ($query) {
            foreach ($query->lazyById(1000) as $o) {
                yield [
                    $o->reference, $o->created_at->toDateTimeString(), $o->paid_at?->toDateTimeString(), $o->awardEvent?->name,
                    $o->category?->name, $o->nominee?->name, $o->votes, (string) $o->amount, $o->currency, $o->status->value,
                    $o->voter_name, $o->voter_email, $o->voter_phone, $o->payment_reference, $o->ip_address,
                ];
            }
        });
    }

    private function filtered(Request $request): Builder
    {
        $query = VoteOrder::query()
            ->when($request->integer('event'), fn ($q, $id) => $q->where('award_event_id', $id))
            ->when(OrderStatus::tryFrom((string) $request->query('status')), fn ($q, $status) => $q->where('status', $status->value))
            ->when($this->like($request->query('q')), fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('reference', 'like', $term)
                ->orWhere('payment_reference', 'like', $term)
                ->orWhere('voter_email', 'like', $term)
                ->orWhere('voter_name', 'like', $term)));

        return $this->applyDateRange($query, $request);
    }
}
