<?php

namespace App\Services;

use App\Models\AwardEvent;
use App\Models\Category;
use App\Models\Nominee;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Ranked results per category. Reads the denormalised nominees.total_votes
 * counter (two queries per event, no N+1) and caches for a short TTL so a
 * traffic spike on the leaderboard never reaches the database.
 */
class LeaderboardService
{
    /**
     * @return Collection<int, array{category: Category, nominees: Collection<int, Nominee>, total: int}>
     */
    public function forEvent(AwardEvent $event): Collection
    {
        return Cache::remember(
            $this->key($event->id),
            (int) config('awards.leaderboard_cache_ttl', 30),
            fn () => $this->build($event),
        );
    }

    /** Uncached, for admin screens that must show the exact current state. */
    public function build(AwardEvent $event): Collection
    {
        $categories = $event->activeCategories()->get();

        $nominees = Nominee::query()
            ->whereIn('category_id', $categories->pluck('id'))
            ->active()
            ->orderByDesc('total_votes')
            ->orderBy('name')
            ->get(['id', 'category_id', 'name', 'slug', 'image', 'total_votes'])
            ->groupBy('category_id');

        return $categories->map(function (Category $category) use ($nominees) {
            $ranked = $nominees->get($category->id, collect())->values();
            $total = (int) $ranked->sum('total_votes');

            $rank = 0;
            $previous = null;
            $ranked->each(function (Nominee $nominee, int $i) use (&$rank, &$previous, $total) {
                // Ties share a rank (1, 2, 2, 4).
                if ($nominee->total_votes !== $previous) {
                    $rank = $i + 1;
                    $previous = $nominee->total_votes;
                }
                $nominee->setAttribute('rank', $rank);
                $nominee->setAttribute('share', $total > 0 ? round($nominee->total_votes / $total * 100, 1) : 0);
            });

            return ['category' => $category, 'nominees' => $ranked, 'total' => $total];
        });
    }

    public function forget(AwardEvent $event): void
    {
        $this->forgetById($event->id);
    }

    public function forgetById(int $eventId): void
    {
        Cache::forget($this->key($eventId));
    }

    public function flushAll(): void
    {
        AwardEvent::query()->pluck('id')->each(fn ($id) => $this->forgetById($id));
    }

    private function key(int $eventId): string
    {
        return "leaderboard:event:{$eventId}";
    }
}
