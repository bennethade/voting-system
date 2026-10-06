<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Nominee extends Model
{
    use HasFactory;

    public const SOCIAL_NETWORKS = ['instagram', 'x', 'tiktok', 'youtube', 'facebook', 'linkedin'];

    /** total_votes is intentionally not fillable: only VotingService changes it. */
    protected $fillable = [
        'category_id', 'name', 'slug', 'short_description', 'bio', 'image', 'cover_image',
        'social_links', 'website', 'sort_order', 'status', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'metadata' => 'array',
            'total_votes' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function voteOrders(): HasMany
    {
        return $this->hasMany(VoteOrder::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /** @return array<string, string> non-empty social links only */
    public function socialLinks(): array
    {
        return array_filter($this->social_links ?? [], fn ($url) => filled($url));
    }
}
