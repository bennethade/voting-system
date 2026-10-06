<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\NomineeRequest;
use App\Models\AwardEvent;
use App\Models\Category;
use App\Models\Nominee;
use App\Models\Vote;
use App\Models\VoteOrder;
use App\Services\AuditLogger;
use App\Services\LeaderboardService;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class NomineeController extends AdminController
{
    public function __construct(
        AuditLogger $audit,
        private readonly MediaService $media,
        private readonly LeaderboardService $leaderboard,
    ) {
        parent::__construct($audit);
    }

    public function index(Request $request): View
    {
        $eventId = $request->integer('event') ?: null;

        $nominees = Nominee::query()
            ->with(['category:id,award_event_id,name', 'category.awardEvent:id,name'])
            ->when($eventId, fn ($q, $id) => $q->whereHas('category', fn ($c) => $c->where('award_event_id', $id)))
            ->when($request->integer('category'), fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($this->like($request->query('q')), fn ($q, $term) => $q->where('name', 'like', $term))
            ->tap(fn ($q) => $this->applySort($q, $request, [
                'name' => 'name',
                'total_votes' => 'total_votes',
                'created_at' => 'created_at',
            ]))
            ->paginate(25)
            ->withQueryString();

        return view('admin.nominees.index', [
            'nominees' => $nominees,
            'events' => AwardEvent::query()->orderByDesc('id')->pluck('name', 'id'),
            'categories' => Category::query()
                ->when($eventId, fn ($q, $id) => $q->where('award_event_id', $id))
                ->orderBy('name')
                ->pluck('name', 'id'),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.nominees.form', [
            'nominee' => new Nominee([
                'category_id' => $request->integer('category') ?: null,
                'status' => 'active',
                'sort_order' => 0,
            ]),
            'categoryGroups' => $this->categoryGroups(),
        ]);
    }

    public function store(NomineeRequest $request): RedirectResponse
    {
        $nominee = new Nominee($request->nomineeData());
        $this->storeImages($request, $nominee);
        $nominee->save();

        $this->audit->created($nominee, "Created nominee {$nominee->name}");
        $this->forgetLeaderboard($nominee->category_id);

        return redirect()
            ->route('admin.nominees.index', ['category' => $nominee->category_id])
            ->with('success', 'Nominee created.');
    }

    public function edit(Nominee $nominee): View
    {
        return view('admin.nominees.form', [
            'nominee' => $nominee,
            'categoryGroups' => $this->categoryGroups(),
        ]);
    }

    public function update(NomineeRequest $request, Nominee $nominee): RedirectResponse
    {
        $previousCategoryId = $nominee->category_id;

        $nominee->fill($request->nomineeData());
        $this->storeImages($request, $nominee);

        $this->audit->updated($nominee, "Updated nominee {$nominee->name}");
        $nominee->save();

        $this->forgetLeaderboard($previousCategoryId);
        if ((int) $nominee->category_id !== (int) $previousCategoryId) {
            $this->forgetLeaderboard((int) $nominee->category_id);
        }

        return redirect()
            ->route('admin.nominees.index', ['category' => $nominee->category_id])
            ->with('success', 'Nominee updated.');
    }

    public function destroy(Nominee $nominee): RedirectResponse
    {
        // Votes and orders are part of the voting/financial record.
        if (Vote::where('nominee_id', $nominee->id)->exists() || VoteOrder::where('nominee_id', $nominee->id)->exists()) {
            return back()->with('error', 'This nominee has votes or orders and cannot be deleted. Set their status to Inactive instead.');
        }

        $this->audit->deleted($nominee, "Deleted nominee {$nominee->name}");
        $this->media->delete($nominee->image);
        $this->media->delete($nominee->cover_image);
        $nominee->delete();
        $this->forgetLeaderboard($nominee->category_id);

        return back()->with('success', 'Nominee deleted.');
    }

    /**
     * Categories grouped by event name for the <optgroup> select.
     *
     * @return Collection<string, Collection<int, string>> event name => [category id => name]
     */
    private function categoryGroups(): Collection
    {
        return Category::query()
            ->with('awardEvent:id,name')
            ->orderByDesc('award_event_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'award_event_id', 'name'])
            ->groupBy(fn (Category $category) => $category->awardEvent?->name ?? 'No event')
            ->map(fn (Collection $group) => $group->pluck('name', 'id'));
    }

    private function storeImages(NomineeRequest $request, Nominee $nominee): void
    {
        foreach (['image' => 'nominees', 'cover_image' => 'nominees/covers'] as $field => $dir) {
            if ($request->hasFile($field)) {
                $nominee->{$field} = $this->media->storeImage($request->file($field), $dir, $nominee->getOriginal($field));
            }
        }
    }

    private function forgetLeaderboard(?int $categoryId): void
    {
        $eventId = $categoryId ? Category::query()->whereKey($categoryId)->value('award_event_id') : null;

        if ($eventId) {
            $this->leaderboard->forgetById((int) $eventId);
        }
    }
}
