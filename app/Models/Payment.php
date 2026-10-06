<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends Model
{
    protected $fillable = [
        'reference', 'payable_type', 'payable_id', 'user_id', 'provider', 'provider_transaction_id',
        'email', 'amount', 'currency', 'status', 'channel', 'gateway_response', 'verified_via',
        'authorization_url', 'paid_at', 'refunded_at', 'ip_address', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PaymentStatus::class,
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Amount in the currency's subunit (kobo for NGN), as Paystack expects. */
    public function amountInSubunit(): int
    {
        return to_subunit($this->amount);
    }
}
