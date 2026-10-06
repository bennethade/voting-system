<?php

namespace App\Http\Controllers;

use App\Models\AwardEvent;
use App\Models\Nominee;
use App\Models\Page;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $xml = Cache::remember('sitemap.xml', 3600, function () {
            $urls = collect([
                ['loc' => route('home'), 'priority' => '1.0'],
                ['loc' => route('voting.index'), 'priority' => '0.9'],
                ['loc' => route('leaderboard'), 'priority' => '0.8'],
                ['loc' => route('events.index'), 'priority' => '0.7'],
                ['loc' => route('gallery'), 'priority' => '0.6'],
                ['loc' => route('bookings.index'), 'priority' => '0.6'],
                ['loc' => route('donate'), 'priority' => '0.5'],
                ['loc' => route('faq'), 'priority' => '0.5'],
                ['loc' => route('contact'), 'priority' => '0.4'],
            ]);

            $events = AwardEvent::publiclyVisible()->with(['activeCategories:id,award_event_id,slug,updated_at'])->get();

            foreach ($events as $event) {
                $urls->push(['loc' => route('events.show', $event), 'lastmod' => $event->updated_at, 'priority' => '0.7']);
                $urls->push(['loc' => route('voting.event', $event), 'lastmod' => $event->updated_at, 'priority' => '0.8']);

                foreach ($event->activeCategories as $category) {
                    $urls->push(['loc' => route('voting.category', [$event, $category]), 'lastmod' => $category->updated_at, 'priority' => '0.7']);
                }
            }

            Nominee::active()
                ->whereHas('category', fn ($q) => $q->active()->whereIn('award_event_id', $events->pluck('id')))
                ->select('slug', 'updated_at')
                ->orderBy('id')
                ->each(fn ($n) => $urls->push(['loc' => route('nominees.show', $n->slug), 'lastmod' => $n->updated_at, 'priority' => '0.6']));

            Page::published()->get(['slug', 'updated_at'])
                ->each(fn ($p) => $urls->push(['loc' => route('pages.show', $p->slug), 'lastmod' => $p->updated_at, 'priority' => '0.3']));

            return view('seo.sitemap', ['urls' => $urls])->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $lines = app()->isProduction()
            ? ['User-agent: *', 'Disallow: /admin', 'Disallow: /voter', 'Disallow: /checkout', 'Disallow: /payment', 'Disallow: /receipts', 'Allow: /', '', 'Sitemap: '.route('sitemap')]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain']);
    }
}
