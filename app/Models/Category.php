<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'award_event_id', 'name', 'slug', 'description', 'image', 'sort_order', 'status',
    ];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function awardEvent(): BelongsTo
    {
        return $this->belongsTo(AwardEvent::class);
    }

    public function nominees(): HasMany
    {
        return $this->hasMany(Nominee::class);
    }

    public function activeNominees(): HasMany
    {
        return $this->nominees()->where('status', 'active')->orderBy('sort_order')->orderBy('name');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
