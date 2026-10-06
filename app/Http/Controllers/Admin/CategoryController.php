<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\CategoryRequest;
use App\Models\AwardEvent;
use App\Models\Category;
use App\Models\Vote;
use App\Models\VoteOrder;
use App\Services\AuditLogger;
use App\Services\LeaderboardService;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CategoryController extends AdminController
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
        $categories = Category::query()
            ->with('awardEvent:id,name')
            ->withCount('nominees')
            ->when($request->integer('event'), fn ($q, $eventId) => $q->where('award_event_id', $eventId))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($this->like($request->query('q')), fn ($q, $term) => $q->where('name', 'like', $term))
            ->tap(fn ($q) => $this->applySort($q, $request, [
                'name' => 'name',
                'sort_order' => 'sort_order',
                'created_at' => 'created_at',
            ], 'sort_order', 'asc'))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.categories.index', [
            'categories' => $categories,
            'events' => $this->eventOptions(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.categories.form', [
            'category' => new Category([
                'award_event_id' => $request->integer('event') ?: null,
                'status' => 'active',
                'sort_order' => 0,
            ]),
            'events' => $this->eventOptions(),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = new Category($request->categoryData());
        $this->storeImage($request, $category);
        $category->save();

        $this->audit->created($category, "Created category {$category->name}");
        $this->leaderboard->forgetById($category->award_event_id);

        return redirect()
            ->route('admin.categories.index', ['event' => $category->award_event_id])
            ->with('success', 'Category created.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', [
            'category' => $category,
            'events' => $this->eventOptions(),
        ]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $previousEventId = $category->award_event_id;

        $category->fill($request->categoryData());
        $this->storeImage($request, $category);

        $this->audit->updated($category, "Updated category {$category->name}");
        $category->save();

        $this->leaderboard->forgetById($previousEventId);
        $this->leaderboard->forgetById($category->award_event_id);

        return redirect()
            ->route('admin.categories.index', ['event' => $category->award_event_id])
            ->with('success', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        // Votes and orders are part of the voting/financial record.
        if (Vote::where('category_id', $category->id)->exists() || VoteOrder::where('category_id', $category->id)->exists()) {
            return back()->with('error', 'This category has votes or orders and cannot be deleted. Set its status to Inactive instead.');
        }

        $this->audit->deleted($category, "Deleted category {$category->name}");
        // Nominees are removed by the FK cascade; clean up their uploads too.
        $category->nominees()->get(['id', 'image', 'cover_image'])->each(function ($nominee) {
            $this->media->delete($nominee->image);
            $this->media->delete($nominee->cover_image);
        });
        $this->media->delete($category->image);
        $category->delete();
        $this->leaderboard->forgetById($category->award_event_id);

        return back()->with('success', 'Category deleted.');
    }

    /** @return Collection<int, string> id => name */
    private function eventOptions(): Collection
    {
        return AwardEvent::query()->orderByDesc('id')->pluck('name', 'id');
    }

    private function storeImage(CategoryRequest $request, Category $category): void
    {
        if ($request->hasFile('image')) {
            $category->image = $this->media->storeImage($request->file('image'), 'categories', $category->getOriginal('image'));
        }
    }
}
