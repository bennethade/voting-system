<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventPackage extends Model
{
    /** quantity_sold is intentionally not fillable: only BookingService changes it. */
    protected $fillable = [
        'award_event_id', 'name', 'price', 'seats', 'description', 'benefits',
        'quantity_available', 'sort_order', 'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'seats' => 'integer',
            'benefits' => 'array',
            'quantity_available' => 'integer',
            'quantity_sold' => 'integer',
        ];
    }

    public function awardEvent(): BelongsTo
    {
        return $this->belongsTo(AwardEvent::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(EventBooking::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function remaining(): int
    {
        return max(0, $this->quantity_available - $this->quantity_sold);
    }
}
