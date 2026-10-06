<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\PaymentWebhookLog;
use App\Services\PaymentService;
use App\Services\PaystackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * POST /webhooks/paystack
 *
 * 1. Verify the HMAC-SHA512 signature over the *raw* body.
 * 2. Log the delivery.
 * 3. Hand charge.success to PaymentService, which locks the payment and
 *    payable, checks reference/amount/currency/status, and fulfils exactly
 *    once. Re-deliveries are acknowledged without side effects.
 */
class PaystackWebhookController extends Controller
{
    public function __invoke(Request $request, PaystackService $paystack, PaymentService $payments): JsonResponse
    {
        $raw = $request->getContent();
        $validSignature = $paystack->isValidSignature($raw, $request->header('x-paystack-signature'));
        $payload = json_decode($raw, true);
        $event = is_array($payload) ? (string) Arr::get($payload, 'event') : null;
        $data = is_array($payload) ? (array) Arr::get($payload, 'data', []) : [];

        $log = PaymentWebhookLog::create([
            'provider' => 'paystack',
            'event' => $event ? mb_substr($event, 0, 64) : null,
            'reference' => isset($data['reference']) ? mb_substr((string) $data['reference'], 0, 64) : null,
            'signature_valid' => $validSignature,
            'ip_address' => $request->ip(),
            // Only keep payloads we could authenticate.
            'payload' => $validSignature ? $payload : null,
        ]);

        if (! $validSignature) {
            Log::warning('Rejected Paystack webhook with invalid signature', ['ip' => $request->ip()]);
            $log->update(['result' => 'invalid_signature']);

            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        try {
            $result = match ($event) {
                'charge.success' => $payments->processTransaction($data, 'webhook'),
                default => 'ignored',
            };
        } catch (Throwable $e) {
            report($e);
            $log->update(['result' => 'error']);

            // 500 makes Paystack retry later; processing is idempotent.
            return response()->json(['message' => 'Processing error.'], 500);
        }

        $log->update(['result' => $result, 'processed_at' => now()]);

        return response()->json(['message' => 'OK']);
    }
}
