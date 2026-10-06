<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Enums\FreeVoteScope;
use App\Enums\ResultsVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Arr;

class AwardEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'short_description', 'description', 'logo', 'banner', 'venue',
        'start_date', 'voting_start_at', 'voting_end_at', 'event_date', 'status',
        'results_visibility', 'is_featured', 'settings',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'voting_start_at' => 'datetime',
            'voting_end_at' => 'datetime',
            'event_date' => 'datetime',
            'results_locked_at' => 'datetime',
            'status' => EventStatus::class,
            'results_visibility' => ResultsVisibility::class,
            'is_featured' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function activeCategories(): HasMany
    {
        return $this->categories()->where('status', 'active')->orderBy('sort_order')->orderBy('name');
    }

    public function nominees(): HasManyThrough
    {
        return $this->hasManyThrough(Nominee::class, Category::class);
    }

    public function votingPackages(): HasMany
    {
        return $this->hasMany(VotingPackage::class);
    }

    public function activePackages(): HasMany
    {
        return $this->votingPackages()->where('status', 'active')->orderBy('sort_order')->orderBy('amount');
    }

    public function eventPackages(): HasMany
    {
        return $this->hasMany(EventPackage::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function galleryItems(): HasMany
    {
        return $this->hasMany(GalleryItem::class);
    }

    public function voteOrders(): HasMany
    {
        return $this->hasMany(VoteOrder::class);
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->whereIn('status', array_map(fn ($s) => $s->value, EventStatus::public()));
    }

    /**
     * The event the public site focuses on by default: one that is open for
     * voting first, then featured, then the most recent.
     */
    public static function current(): ?self
    {
        return static::query()
            ->publiclyVisible()
            ->orderByRaw('CASE status WHEN ? THEN 0 WHEN ? THEN 1 WHEN ? THEN 2 ELSE 3 END', [
                EventStatus::Voting->value, EventStatus::Paused->value, EventStatus::Upcoming->value,
            ])
            ->orderByDesc('is_featured')
            ->orderByDesc('voting_start_at')
            ->orderByDesc('id')
            ->first();
    }

    /** Resolve ?event=slug against public events, falling back to current(). */
    public static function fromRequestOrCurrent(?string $slug): ?self
    {
        if ($slug) {
            return static::query()->publiclyVisible()->where('slug', $slug)->first() ?? static::current();
        }

        return static::current();
    }

    public function isPubliclyVisible(): bool
    {
        return in_array($this->status, EventStatus::public(), true);
    }

    /** Event setting with platform defaults applied. */
    public function setting(string $key, mixed $default = null): mixed
    {
        $settings = array_merge(config('awards.event_defaults', []), $this->settings ?? []);

        return Arr::get($settings, $key, $default);
    }

    public function freeVoteScope(): FreeVoteScope
    {
        return FreeVoteScope::tryFrom((string) $this->setting('free_vote_scope')) ?? FreeVoteScope::Category;
    }

    public function currency(): string
    {
        return $this->setting('currency', config('awards.currency'));
    }

    public function isResultsLocked(): bool
    {
        return $this->results_locked_at !== null;
    }

    /**
     * Null when voting is open, otherwise a user-facing reason. Time windows
     * are enforced here so voting stops automatically at `voting_end_at`
     * even before the scheduler flips the status.
     */
    public function votingClosedReason(): ?string
    {
        $now = now();

        if ($this->isResultsLocked()) {
            return 'Voting has ended and the results have been finalised.';
        }

        return match (true) {
            $this->status === EventStatus::Paused => 'Voting is temporarily paused. Please check back shortly.',
            $this->status === EventStatus::Draft, $this->status === EventStatus::Upcoming,
            $this->voting_start_at && $now->lt($this->voting_start_at) => 'Voting has not started yet.',
            $this->status !== EventStatus::Voting,
            $this->voting_end_at && $now->gte($this->voting_end_at) => 'The voting period has ended.',
            default => null,
        };
    }

    public function isVotingOpen(): bool
    {
        return $this->votingClosedReason() === null;
    }

    public function hasVotingEnded(): bool
    {
        return in_array($this->status, [EventStatus::Closed, EventStatus::Completed], true)
            || ($this->voting_end_at && now()->gte($this->voting_end_at))
            || $this->isResultsLocked();
    }

    public function resultsArePublic(): bool
    {
        return match ($this->results_visibility) {
            ResultsVisibility::Live => true,
            ResultsVisibility::AfterVoting => $this->hasVotingEnded(),
            default => false,
        };
    }
}
