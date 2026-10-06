<?php

namespace App\Http\Controllers;

use App\Models\AwardEvent;
use App\Models\GalleryItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GalleryController extends Controller
{
    public function index(Request $request): View
    {
        $events = AwardEvent::publiclyVisible()->whereHas('galleryItems')->orderByDesc('id')->get(['id', 'name', 'slug']);
        $selected = $events->firstWhere('slug', $request->query('event'));

        $items = GalleryItem::active()
            ->when($selected, fn ($q) => $q->where('award_event_id', $selected->id))
            ->when($request->query('type') && array_key_exists($request->query('type'), GalleryItem::TYPES), fn ($q) => $q->where('type', $request->query('type')))
            ->orderBy('sort_order')
            ->latest()
            ->paginate(24)
            ->withQueryString();

        return view('gallery', compact('items', 'events', 'selected'));
    }
}
