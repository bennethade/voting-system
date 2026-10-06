<?php

namespace App\Models;

use App\Contracts\Payable;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class VoteOrder extends Model implements Payable
{
    use HasFactory;

    protected $fillable = [
        'reference', 'award_event_id', 'category_id', 'nominee_id', 'voting_package_id', 'user_id',
        'voter_name', 'voter_email', 'voter_phone', 'amount', 'votes', 'currency', 'payment_provider',
        'payment_reference', 'status', 'paid_at', 'ip_address', 'user_agent', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'votes' => 'integer',
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function nominee(): BelongsTo
    {
        return $this->belongsTo(Nominee::class);
    }

    public function votingPackage(): BelongsTo
    {
        return $this->belongsTo(VotingPackage::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vote(): HasOne
    {
        return $this->hasOne(Vote::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function isPaid(): bool
    {
        return $this->status === OrderStatus::Paid;
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
        return $this->voter_email;
    }

    public function payableDescription(): string
    {
        return "{$this->votes} votes for {$this->nominee?->name}";
    }

    public function payableStatus(): OrderStatus
    {
        return $this->status;
    }
}
