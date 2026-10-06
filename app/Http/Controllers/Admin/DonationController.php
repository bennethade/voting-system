<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Models\AwardEvent;
use App\Models\Donation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DonationController extends AdminController
{
    public function index(Request $request): View
    {
        $paidTotal = (string) $this->filtered($request)->where('status', OrderStatus::Paid->value)->sum('amount');
        $paidCount = $this->filtered($request)->where('status', OrderStatus::Paid->value)->count();

        $donations = $this->filtered($request)
            ->with('awardEvent:id,name')
            ->tap(fn ($q) => $this->applySort($q, $request, ['amount' => 'amount', 'created_at' => 'created_at', 'paid_at' => 'paid_at']))
            ->paginate(25)
            ->withQueryString();

        $events = AwardEvent::orderBy('name')->pluck('name', 'id');

        return view('admin.donations.index', compact('donations', 'paidTotal', 'paidCount', 'events'));
    }

    public function show(Donation $donation): View
    {
        $donation->load(['awardEvent:id,name', 'user:id,name,email,role']);
        $payments = $donation->payments()->latest()->get();

        return view('admin.donations.show', compact('donation', 'payments'));
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->filtered($request)->with('awardEvent:id,name');

        return $this->csv('donations', [
            'Reference', 'Date', 'Donor', 'Email', 'Anonymous', 'Event', 'Amount', 'Currency',
            'Status', 'Payment reference', 'Paid at', 'Message',
        ], function () use ($query) {
            foreach ($query->lazyById(1000) as $d) {
                yield [
                    $d->reference,
                    $d->created_at?->format('Y-m-d H:i:s'),
                    $d->donor_name,
                    $d->email,
                    $d->is_anonymous ? 'yes' : 'no',
                    $d->awardEvent?->name,
                    $d->amount,
                    $d->currency,
                    $d->status,
                    $d->payment_reference,
                    $d->paid_at?->format('Y-m-d H:i:s'),
                    $d->message,
                ];
            }
        });
    }

    /** Base query with the listing filters applied (shared by list, totals and export). */
    private function filtered(Request $request): Builder
    {
        $statuses = collect(OrderStatus::cases())->map->value->all();

        return Donation::query()
            ->when($this->like($request->query('q')), fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('reference', 'like', $term)
                ->orWhere('donor_name', 'like', $term)
                ->orWhere('email', 'like', $term)
                ->orWhere('payment_reference', 'like', $term)))
            ->when(in_array($request->query('status'), $statuses, true), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->integer('event'), fn ($q, $event) => $q->where('award_event_id', $event))
            ->tap(fn ($q) => $this->applyDateRange($q, $request));
    }
}
