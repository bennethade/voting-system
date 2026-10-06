<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\GalleryItemRequest;
use App\Models\AwardEvent;
use App\Models\GalleryItem;
use App\Services\AuditLogger;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GalleryController extends AdminController
{
    public function __construct(AuditLogger $audit, private readonly MediaService $media)
    {
        parent::__construct($audit);
    }

    public function index(Request $request): View
    {
        $items = GalleryItem::query()
            ->with('awardEvent:id,name')
            ->when($this->like($request->query('q')), fn ($q, $term) => $q->where(fn ($w) => $w->where('title', 'like', $term)->orWhere('caption', 'like', $term)))
            ->when($request->integer('event'), fn ($q, $event) => $q->where('award_event_id', $event))
            ->when($request->query('type'), fn ($q, $type) => $q->where('type', $type))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->tap(fn ($q) => $this->applySort($q, $request, ['title' => 'title', 'created_at' => 'created_at', 'sort_order' => 'sort_order'], 'sort_order', 'asc'))
            ->orderByDesc('id')
            ->paginate(24)
            ->withQueryString();

        return view('admin.gallery.index', [
            'items' => $items,
            'events' => $this->eventOptions(),
        ]);
    }

    public function create(): View
    {
        return view('admin.gallery.form', [
            'item' => new GalleryItem(['type' => 'image', 'status' => 'active', 'sort_order' => 0]),
            'events' => $this->eventOptions(),
        ]);
    }

    public function store(GalleryItemRequest $request): RedirectResponse
    {
        $item = new GalleryItem($request->itemData());
        $this->storeFiles($request, $item);
        $item->save();

        $this->audit->created($item, "Created gallery item {$item->title}");

        return redirect()->route('admin.gallery.index')->with('success', 'Gallery item created.');
    }

    public function edit(GalleryItem $galleryItem): View
    {
        return view('admin.gallery.form', [
            'item' => $galleryItem,
            'events' => $this->eventOptions(),
        ]);
    }

    public function update(GalleryItemRequest $request, GalleryItem $galleryItem): RedirectResponse
    {
        $galleryItem->fill($request->itemData());

        // A YouTube item has no use for a previously uploaded file.
        if ($galleryItem->type === 'youtube' && $galleryItem->getOriginal('file_path')) {
            $this->media->delete($galleryItem->getOriginal('file_path'));
            $galleryItem->file_path = null;
        }

        $this->storeFiles($request, $galleryItem);

        $this->audit->updated($galleryItem, "Updated gallery item {$galleryItem->title}");
        $galleryItem->save();

        return redirect()->route('admin.gallery.index')->with('success', 'Gallery item updated.');
    }

    public function destroy(GalleryItem $galleryItem): RedirectResponse
    {
        $this->audit->deleted($galleryItem, "Deleted gallery item {$galleryItem->title}");
        $this->media->delete($galleryItem->file_path);
        $this->media->delete($galleryItem->thumbnail);
        $galleryItem->delete();

        return redirect()->route('admin.gallery.index')->with('success', 'Gallery item deleted.');
    }

    private function storeFiles(GalleryItemRequest $request, GalleryItem $item): void
    {
        if ($item->type === 'image' && $request->hasFile('image')) {
            $item->file_path = $this->media->storeImage($request->file('image'), 'gallery', $item->getOriginal('file_path'));
        }

        if ($item->type === 'video' && $request->hasFile('video')) {
            $item->file_path = $this->media->storeFile($request->file('video'), 'gallery/videos', $item->getOriginal('file_path'));
        }

        if ($request->hasFile('thumbnail')) {
            $item->thumbnail = $this->media->storeImage($request->file('thumbnail'), 'gallery/thumbnails', $item->getOriginal('thumbnail'));
        }
    }

    /** @return array<int, string> */
    private function eventOptions(): array
    {
        return AwardEvent::query()->orderByDesc('id')->pluck('name', 'id')->all();
    }
}
