<?php

namespace App\Models;

use App\Enums\VoteType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only vote ledger. Rows are never edited to change a count: changes
 * are expressed as new `admin_adjustment` rows, and suspicious rows are
 * marked `invalidated` (with who/when) rather than deleted.
 */
class Vote extends Model
{
    use HasFactory;

    public const STATUS_VALID = 'valid';

    public const STATUS_INVALIDATED = 'invalidated';

    protected $fillable = [
        'award_event_id', 'category_id', 'nominee_id', 'user_id', 'vote_order_id', 'vote_type',
        'votes', 'status', 'free_vote_key', 'admin_user_id', 'reason', 'ip_address', 'user_agent', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'vote_type' => VoteType::class,
            'votes' => 'integer',
            'metadata' => 'array',
            'invalidated_at' => 'datetime',
        ];
    }

    public function awardEvent(): BelongsTo
    {
        return $this->belongsTo(AwardEvent::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function nominee(): BelongsTo
    {
        return $this->belongsTo(Nominee::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function voteOrder(): BelongsTo
    {
        return $this->belongsTo(VoteOrder::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function invalidator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invalidated_by');
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('status'), self::STATUS_VALID);
    }

    public function isValid(): bool
    {
        return $this->status === self::STATUS_VALID;
    }
}
