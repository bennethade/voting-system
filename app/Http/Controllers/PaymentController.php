<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentException;
use App\Models\Donation;
use App\Models\EventBooking;
use App\Models\Payment;
use App\Models\VoteOrder;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    /**
     * Where Paystack redirects the customer. Query parameters are used only
     * to find *which* payment to check; its outcome comes from a
     * server-to-server verify call (or the webhook, whichever lands first).
     */
    public function callback(Request $request): RedirectResponse
    {
        $reference = (string) ($request->query('reference') ?: $request->query('trxref'));
        $payment = Payment::where('reference', $reference)->first();

        if (! $payment) {
            return redirect()->route('home')->with('error', 'We could not find that payment.');
        }

        try {
            $this->payments->verify($payment, 'callback');
        } catch (PaymentException) {
            // Gateway unreachable: the status page shows "pending" and polls,
            // and the webhook will complete the payment.
        }

        return redirect(self::statusUrl($payment));
    }

    public function status(Payment $payment): View
    {
        $payment->load('payable');
        $payable = $payment->payable;

        if ($payable instanceof VoteOrder) {
            $payable->load('nominee', 'category');
        } elseif ($payable instanceof EventBooking) {
            $payable->load('eventPackage', 'awardEvent');
        }

        return view('payments.status', [
            'payment' => $payment,
            'payable' => $payable,
            'checkUrl' => URL::temporarySignedRoute('payment.check', now()->addHour(), ['payment' => $payment->reference]),
            'receiptUrl' => $this->receiptUrl($payable),
        ]);
    }

    /** Polled by the pending page. Re-verifies with Paystack while pending. */
    public function check(Payment $payment): JsonResponse
    {
        if ($payment->status === PaymentStatus::Pending && $payment->created_at->gt(now()->subDay())) {
            try {
                $this->payments->verify($payment, 'callback');
            } catch (PaymentException) {
                // still pending
            }
        }

        return response()->json(['status' => $payment->refresh()->status->value]);
    }

    public static function statusUrl(Payment $payment): string
    {
        return URL::temporarySignedRoute('payment.status', now()->addDays(30), ['payment' => $payment->reference]);
    }

    private function receiptUrl(mixed $payable): ?string
    {
        return match (true) {
            $payable instanceof VoteOrder && $payable->isPaid() => ReceiptController::voteUrl($payable),
            $payable instanceof EventBooking && $payable->status === OrderStatus::Paid => ReceiptController::bookingUrl($payable),
            $payable instanceof Donation => null,
            default => null,
        };
    }
}
