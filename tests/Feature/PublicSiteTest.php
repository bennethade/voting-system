<?php

namespace Tests\Feature;

use App\Enums\ResultsVisibility;
use App\Models\ContactMessage;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_render(): void
    {
        ['event' => $event, 'category' => $category, 'nominee' => $nominee] = $this->votingSetup();
        $page = new Page(['title' => 'About', 'slug' => 'about', 'content' => '<p>Hi</p>', 'status' => 'published']);
        $page->save();

        foreach ([
            route('home'),
            route('voting.event', $event),
            route('voting.category', [$event, $category]),
            route('nominees.show', $nominee),
            route('votes.checkout', $nominee),
            route('leaderboard'),
            route('events.index'),
            route('events.show', $event),
            route('gallery'),
            route('faq'),
            route('contact'),
            route('donate'),
            route('bookings.index'),
            route('about'),
            route('login'),
            route('register'),
            route('sitemap'),
            route('robots'),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_nominee_page_has_seo_metadata(): void
    {
        ['nominee' => $nominee] = $this->votingSetup();

        $this->get(route('nominees.show', $nominee))
            ->assertSee('<link rel="canonical"', false)
            ->assertSee('og:title', false)
            ->assertSee('application/ld+json', false);
    }

    public function test_leaderboard_hides_counts_when_results_hidden(): void
    {
        ['event' => $event, 'nominee' => $nominee] = $this->votingSetup([], ['results_visibility' => ResultsVisibility::Hidden]);
        $nominee->forceFill(['total_votes' => 4321])->save();

        $this->get(route('leaderboard'))->assertOk()->assertSee('Results are hidden')->assertDontSee('4,321');
        $this->get(route('nominees.show', $nominee))->assertDontSee('4,321');

        $event->update(['results_visibility' => ResultsVisibility::Live]);
        $this->get(route('leaderboard', ['event' => $event->slug]))->assertSee('4,321');
    }

    public function test_after_voting_visibility_only_shows_once_closed(): void
    {
        ['event' => $event, 'nominee' => $nominee] = $this->votingSetup([], ['results_visibility' => ResultsVisibility::AfterVoting]);
        $nominee->forceFill(['total_votes' => 777])->save();

        $this->get(route('leaderboard'))->assertDontSee('777');

        $event->update(['voting_end_at' => now()->subMinute()]);
        $this->get(route('leaderboard', ['event' => $event->slug]))->assertSee('777');
    }

    public function test_draft_events_are_not_public(): void
    {
        ['event' => $event, 'nominee' => $nominee] = $this->votingSetup([], ['status' => 'draft']);

        $this->get(route('voting.event', $event))->assertNotFound();
        $this->get(route('nominees.show', $nominee))->assertNotFound();
    }

    public function test_contact_form_stores_message_and_blocks_bots(): void
    {
        // Three submissions in a row would otherwise trip the contact rate limit.
        $this->withoutMiddleware(ThrottleRequests::class);

        $payload = [
            'name' => 'Ola', 'email' => 'ola@example.com', 'subject' => 'Hello',
            'message' => 'I would like to partner with you.',
            'form_token' => Crypt::encryptString((string) (time() - 10)),
        ];

        $this->post(route('contact.store'), $payload)->assertSessionHas('success');
        $this->assertSame(1, ContactMessage::count());

        $this->post(route('contact.store'), [...$payload, 'company' => 'spam'])->assertSessionHasErrors('company');
        $this->post(route('contact.store'), [...$payload, 'form_token' => Crypt::encryptString((string) time())])->assertSessionHasErrors('message');
        $this->assertSame(1, ContactMessage::count());
    }
}
