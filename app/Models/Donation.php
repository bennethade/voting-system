<?php

namespace App\Models;

use App\Contracts\Payable;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Donation extends Model implements Payable
{
    protected $fillable = [
        'reference', 'user_id', 'award_event_id', 'donor_name', 'email', 'amount', 'currency',
        'payment_reference', 'status', 'message', 'is_anonymous', 'paid_at', 'ip_address', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => OrderStatus::class,
            'is_anonymous' => 'boolean',
            'paid_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function awardEvent(): BelongsTo
    {
        return $this->belongsTo(AwardEvent::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function payableAmount(): string
    {
        return (string) $this->amount;
    }

    public function payableCurrency(): string
    {
        return $this->currency;
    }

    public function payableEmail(): string
    {
        return $this->email;
    }

    public function payableDescription(): string
    {
        return 'Donation';
    }

    public function payableStatus(): OrderStatus
    {
        return $this->status;
    }
}
