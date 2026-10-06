<?php

namespace App\Models;

use App\Contracts\Payable;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class EventBooking extends Model implements Payable
{
    protected $fillable = [
        'reference', 'award_event_id', 'event_package_id', 'user_id', 'contact_name', 'contact_email',
        'contact_phone', 'organization', 'quantity', 'seats', 'unit_price', 'amount', 'currency',
        'attendees', 'notes', 'payment_reference', 'status', 'paid_at', 'ip_address', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'amount' => 'decimal:2',
            'quantity' => 'integer',
            'seats' => 'integer',
            'attendees' => 'array',
            'status' => OrderStatus::class,
            'paid_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function awardEvent(): BelongsTo
    {
        return $this->belongsTo(AwardEvent::class);
    }

    public function eventPackage(): BelongsTo
    {
        return $this->belongsTo(EventPackage::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
        return $this->contact_email;
    }

    public function payableDescription(): string
    {
        return "{$this->quantity} x {$this->eventPackage?->name}";
    }

    public function payableStatus(): OrderStatus
    {
        return $this->status;
    }
}
