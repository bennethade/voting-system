<?php

namespace App\Models;

use App\Enums\FraudStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FraudFlag extends Model
{
    public const TYPES = [
        'ip_repeat' => 'Repeated IP activity',
        'multi_account' => 'Multiple accounts from one IP',
        'vote_spike' => 'Vote spike',
        'payment_pattern' => 'Unusual payment pattern',
        'high_activity' => 'Abnormally high voting activity',
        'manual' => 'Manually flagged',
    ];

    protected $fillable = [
        'award_event_id', 'flaggable_type', 'flaggable_id', 'type', 'severity', 'description',
        'ip_address', 'email', 'user_id', 'nominee_id', 'signature', 'status', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => FraudStatus::class,
            'reviewed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function flaggable(): MorphTo
    {
        return $this->morphTo();
    }

    public function awardEvent(): BelongsTo
    {
        return $this->belongsTo(AwardEvent::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function nominee(): BelongsTo
    {
        return $this->belongsTo(Nominee::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
