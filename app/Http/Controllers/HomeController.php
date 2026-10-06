<?php

namespace App\Http\Controllers;

use App\Models\AwardEvent;
use App\Models\GalleryItem;
use App\Models\Nominee;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $event = AwardEvent::current();

        $categories = $event
            ? $event->activeCategories()->withCount(['nominees' => fn ($q) => $q->active()])->get()
            : collect();

        $featuredNominees = $event
            ? Nominee::query()
                ->active()
                ->whereIn('category_id', $categories->pluck('id'))
                ->with('category:id,name,slug,award_event_id')
                ->inRandomOrder()
                ->limit(8)
                ->get()
            : collect();

        $packages = $event ? $event->activePackages()->limit(6)->get() : collect();

        $gallery = GalleryItem::active()->where('type', 'image')->orderBy('sort_order')->latest()->limit(6)->get();

        return view('home', compact('event', 'categories', 'featuredNominees', 'packages', 'gallery'));
    }
}
