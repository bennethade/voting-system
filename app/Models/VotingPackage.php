<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VotingPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'award_event_id', 'name', 'amount', 'votes', 'description', 'sort_order', 'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'votes' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function awardEvent(): BelongsTo
    {
        return $this->belongsTo(AwardEvent::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
