<?php

namespace Tests\Feature\Voting;

use App\Enums\EventStatus;
use App\Enums\VoteType;
use App\Exceptions\VotingException;
use App\Models\Category;
use App\Models\Nominee;
use App\Models\User;
use App\Models\Vote;
use App\Services\VotingService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FreeVoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_voter_can_cast_a_free_vote(): void
    {
        ['nominee' => $nominee] = $this->votingSetup();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('votes.free', $nominee))->assertSessionHas('success');

        $this->assertDatabaseHas('votes', [
            'nominee_id' => $nominee->id,
            'user_id' => $user->id,
            'vote_type' => VoteType::Free->value,
            'votes' => 1,
        ]);
        $this->assertSame(1, $nominee->fresh()->total_votes);
    }

    public function test_duplicate_free_vote_is_rejected(): void
    {
        ['nominee' => $nominee] = $this->votingSetup(['free_vote_scope' => 'category']);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('votes.free', $nominee));
        $this->actingAs($user)->post(route('votes.free', $nominee))
            ->assertSessionHas('error', 'You have already used your free vote in this category.');

        $this->assertSame(1, Vote::count());
        $this->assertSame(1, $nominee->fresh()->total_votes);
    }

    public function test_free_vote_rule_is_enforced_by_the_database(): void
    {
        ['nominee' => $nominee, 'event' => $event, 'category' => $category] = $this->votingSetup();
        $user = User::factory()->create();
        $key = app(VotingService::class)->freeVoteKey($event, $user, $category);

        $attributes = [
            'award_event_id' => $event->id, 'category_id' => $category->id, 'nominee_id' => $nominee->id,
            'user_id' => $user->id, 'vote_type' => 'free', 'votes' => 1, 'free_vote_key' => $key,
        ];
        Vote::create($attributes);

        // Simulates a race where two requests both pass the application check.
        $this->expectException(UniqueConstraintViolationException::class);
        Vote::create($attributes);
    }

    public function test_free_vote_scope_per_event_blocks_other_categories(): void
    {
        ['event' => $event, 'nominee' => $first] = $this->votingSetup(['free_vote_scope' => 'event']);
        $otherCategory = Category::factory()->for($event, 'awardEvent')->create();
        $second = Nominee::factory()->for($otherCategory)->create();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('votes.free', $first))->assertSessionHas('success');
        $this->actingAs($user)->post(route('votes.free', $second))->assertSessionHas('error');

        $this->assertSame(1, Vote::count());
    }

    public function test_free_vote_scope_per_category_allows_other_categories(): void
    {
        ['event' => $event, 'nominee' => $first] = $this->votingSetup(['free_vote_scope' => 'category']);
        $second = Nominee::factory()->for(Category::factory()->for($event, 'awardEvent'))->create();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('votes.free', $first));
        $this->actingAs($user)->post(route('votes.free', $second));

        $this->assertSame(2, Vote::count());
    }

    public function test_daily_scope_allows_a_new_vote_the_next_day(): void
    {
        ['nominee' => $nominee] = $this->votingSetup(['free_vote_scope' => 'category_daily']);
        $user = User::factory()->create();
        $voting = app(VotingService::class);

        $voting->castFreeVote($user, $nominee);
        $this->travel(1)->days();
        $voting->castFreeVote($user, $nominee);

        $this->assertSame(2, $nominee->fresh()->total_votes);
    }

    public function test_unverified_voter_cannot_cast_free_vote_when_verification_required(): void
    {
        ['nominee' => $nominee] = $this->votingSetup(['free_vote_requires_verification' => true]);
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->post(route('votes.free', $nominee))
            ->assertSessionHas('error', 'Please verify your email address before casting your free vote.');

        $this->assertSame(0, Vote::count());
    }

    public function test_guest_must_log_in_to_vote_free(): void
    {
        ['nominee' => $nominee] = $this->votingSetup();

        $this->post(route('votes.free', $nominee))->assertRedirect(route('login'));
        $this->assertSame(0, Vote::count());
    }

    public function test_cannot_vote_when_voting_period_has_ended(): void
    {
        ['nominee' => $nominee] = $this->votingSetup([], ['voting_end_at' => now()->subMinute()]);

        $this->actingAs(User::factory()->create())->post(route('votes.free', $nominee))
            ->assertSessionHas('error', 'The voting period has ended.');

        $this->assertSame(0, Vote::count());
    }

    public function test_cannot_vote_when_event_paused_or_locked(): void
    {
        ['nominee' => $nominee, 'event' => $event] = $this->votingSetup([], ['status' => EventStatus::Paused]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('votes.free', $nominee))->assertSessionHas('error');

        $event->forceFill(['status' => EventStatus::Voting, 'results_locked_at' => now()])->save();
        $this->actingAs($user)->post(route('votes.free', $nominee))->assertSessionHas('error');

        $this->assertSame(0, Vote::count());
    }

    public function test_cannot_vote_for_inactive_nominee(): void
    {
        ['nominee' => $nominee] = $this->votingSetup();
        $nominee->update(['status' => 'inactive']);

        $this->expectException(VotingException::class);
        app(VotingService::class)->castFreeVote(User::factory()->create(), $nominee);
    }

    public function test_unknown_nominee_returns_404(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('votes.free', 'does-not-exist'))
            ->assertNotFound();
    }

    public function test_free_votes_disabled_for_event(): void
    {
        ['nominee' => $nominee] = $this->votingSetup(['free_voting_enabled' => false]);

        $this->actingAs(User::factory()->create())->post(route('votes.free', $nominee))
            ->assertSessionHas('error', 'Free voting is not available for this event.');
    }

    public function test_recalculate_command_repairs_drifted_counters(): void
    {
        ['nominee' => $nominee] = $this->votingSetup();
        app(VotingService::class)->castFreeVote(User::factory()->create(), $nominee);
        Nominee::whereKey($nominee->id)->update(['total_votes' => 999]);

        $this->artisan('votes:recalculate')->assertSuccessful();

        $this->assertSame(1, $nominee->fresh()->total_votes);
    }
}
