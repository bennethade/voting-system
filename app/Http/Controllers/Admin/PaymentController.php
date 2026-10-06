<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Exceptions\PaymentException;
use App\Models\Payment;
use App\Models\PaymentWebhookLog;
use App\Services\PaymentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends AdminController
{
    private const TYPES = ['vote_order' => 'Vote order', 'donation' => 'Donation', 'event_booking' => 'Booking'];

    public function index(Request $request): View
    {
        $payments = $this->filtered($request)
            ->with('payable')
            ->tap(fn ($q) => $this->applySort($q, $request, ['amount' => 'amount', 'created_at' => 'created_at', 'paid_at' => 'paid_at']))
            ->paginate(30)
            ->withQueryString();

        $totals = $this->filtered($request)
            ->selectRaw('COUNT(*) as attempts')
            ->selectRaw('COALESCE(SUM(CASE WHEN status = ? THEN amount ELSE 0 END), 0) as collected', [PaymentStatus::Success->value])
            ->selectRaw('COALESCE(SUM(CASE WHEN status = ? THEN amount ELSE 0 END), 0) as refunded', [PaymentStatus::Refunded->value])
            ->first();

        return view('admin.payments.index', ['payments' => $payments, 'totals' => $totals, 'types' => self::TYPES]);
    }

    public function show(Payment $payment): View
    {
        $payment->load(['payable', 'user']);

        return view('admin.payments.show', [
            'payment' => $payment,
            'types' => self::TYPES,
            'webhooks' => PaymentWebhookLog::where('reference', $payment->reference)->latest()->get(),
        ]);
    }

    /** Ask Paystack for the authoritative status (e.g. a webhook was missed). */
    public function verify(Payment $payment, PaymentService $payments): RedirectResponse
    {
        try {
            $payment = $payments->verify($payment, 'manual');
        } catch (PaymentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->audit->log('payments.reverified', "Re-verified payment {$payment->reference}: {$payment->status->value}", $payment);

        return back()->with('success', "Paystack reports this payment as: {$payment->status->label()}.");
    }

    public function refund(Request $request, Payment $payment, PaymentService $payments): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
            'confirm_reference' => ['required', 'in:'.$payment->reference],
        ], ['confirm_reference.in' => 'Type the payment reference exactly to confirm the refund.']);

        $payments->refund($payment, $request->user(), $data['reason']);

        return back()->with('success', 'Refund submitted to Paystack and recorded. Any votes from this payment were invalidated.');
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->filtered($request)->with('payable');

        return $this->csv('payments', [
            'Payment reference', 'Type', 'Order reference', 'Email', 'Amount', 'Currency', 'Status', 'Channel',
            'Gateway response', 'Verified via', 'Provider transaction ID', 'Created', 'Paid at', 'IP address',
        ], function () use ($query) {
            foreach ($query->lazyById(1000) as $p) {
                yield [
                    $p->reference, self::TYPES[$p->payable_type] ?? $p->payable_type, $p->payable?->reference, $p->email,
                    (string) $p->amount, $p->currency, $p->status->value, $p->channel, $p->gateway_response, $p->verified_via,
                    $p->provider_transaction_id, $p->created_at->toDateTimeString(), $p->paid_at?->toDateTimeString(), $p->ip_address,
                ];
            }
        });
    }

    private function filtered(Request $request): Builder
    {
        $query = Payment::query()
            ->when(PaymentStatus::tryFrom((string) $request->query('status')), fn ($q, $s) => $q->where('status', $s->value))
            ->when(array_key_exists((string) $request->query('type'), self::TYPES) ? $request->query('type') : null, fn ($q, $t) => $q->where('payable_type', $t))
            ->when($this->like($request->query('q')), fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('reference', 'like', $term)
                ->orWhere('email', 'like', $term)
                ->orWhere('provider_transaction_id', 'like', $term)));

        return $this->applyDateRange($query, $request);
    }
}
