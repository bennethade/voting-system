<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Enums\VoteType;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Vote;
use App\Services\VotingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_admin_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.events.index'))->assertRedirect(route('admin.login'));
    }

    public function test_voters_cannot_access_admin(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_super_admin_can_access_everything(): void
    {
        $this->votingSetup();
        $admin = User::factory()->superAdmin()->create();

        foreach (['admin.dashboard', 'admin.events.index', 'admin.votes.index', 'admin.payments.index', 'admin.fraud.index', 'admin.audit-logs.index', 'admin.settings.edit', 'admin.administrators.index', 'admin.leaderboard'] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
    }

    public function test_finance_role_is_limited_to_money(): void
    {
        $finance = User::factory()->role(Role::Finance)->create();

        $this->actingAs($finance)->get(route('admin.payments.index'))->assertOk();
        $this->actingAs($finance)->get(route('admin.orders.index'))->assertOk();

        $this->actingAs($finance)->get(route('admin.nominees.index'))->assertForbidden();
        $this->actingAs($finance)->get(route('admin.events.index'))->assertForbidden();
        $this->actingAs($finance)->get(route('admin.settings.edit'))->assertForbidden();
        $this->actingAs($finance)->post(route('admin.votes.adjust.store'), [])->assertForbidden();
    }

    public function test_voting_manager_can_manage_nominees_but_not_payments(): void
    {
        $manager = User::factory()->role(Role::VotingManager)->create();

        $this->actingAs($manager)->get(route('admin.nominees.index'))->assertOk();
        $this->actingAs($manager)->get(route('admin.votes.index'))->assertOk();
        $this->actingAs($manager)->get(route('admin.payments.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('admin.administrators.index'))->assertForbidden();
    }

    public function test_admin_cannot_manage_administrators_or_settings(): void
    {
        $admin = User::factory()->role(Role::Admin)->create();

        $this->actingAs($admin)->get(route('admin.administrators.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.settings.edit'))->assertForbidden();
    }

    public function test_inactive_staff_are_locked_out(): void
    {
        $admin = User::factory()->superAdmin()->inactive()->create();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertRedirect();
        $this->assertGuest();
    }

    public function test_vote_adjustment_creates_ledger_entry_and_audit_log(): void
    {
        ['nominee' => $nominee] = $this->votingSetup();
        $manager = User::factory()->role(Role::VotingManager)->create();

        $this->actingAs($manager)->post(route('admin.votes.adjust.store'), [
            'nominee_id' => $nominee->id,
            'adjustment' => 25,
            'reason' => 'Offline ballot box count from the venue',
        ])->assertSessionHas('success');

        $vote = Vote::where('vote_type', VoteType::AdminAdjustment->value)->firstOrFail();
        $this->assertSame(25, $vote->votes);
        $this->assertSame($manager->id, $vote->admin_user_id);
        $this->assertSame(0, $vote->metadata['old_total']);
        $this->assertSame(25, $vote->metadata['new_total']);
        $this->assertSame(25, $nominee->fresh()->total_votes);
        $this->assertSame(1, AuditLog::where('action', 'votes.adjusted')->count());
    }

    public function test_adjustment_cannot_make_total_negative(): void
    {
        ['nominee' => $nominee] = $this->votingSetup();

        $this->actingAs(User::factory()->superAdmin()->create())->post(route('admin.votes.adjust.store'), [
            'nominee_id' => $nominee->id, 'adjustment' => -5, 'reason' => 'Testing negative totals',
        ])->assertSessionHas('error');

        $this->assertSame(0, Vote::count());
    }

    public function test_locked_results_block_adjustments_except_for_super_admin(): void
    {
        ['nominee' => $nominee, 'event' => $event] = $this->votingSetup();
        $event->forceFill(['results_locked_at' => now()])->save();
        $payload = ['nominee_id' => $nominee->id, 'adjustment' => 3, 'reason' => 'Post-lock correction'];

        $this->actingAs(User::factory()->role(Role::Admin)->create())->post(route('admin.votes.adjust.store'), $payload)->assertSessionHas('error');
        $this->assertSame(0, Vote::count());

        $this->actingAs(User::factory()->superAdmin()->create())->post(route('admin.votes.adjust.store'), $payload)->assertSessionHas('success');
        $this->assertSame(1, Vote::count());
    }

    public function test_invalidating_a_vote_updates_totals_and_is_audited(): void
    {
        ['nominee' => $nominee] = $this->votingSetup();
        $vote = app(VotingService::class)->castFreeVote(User::factory()->create(), $nominee);

        $this->actingAs(User::factory()->role(Role::Admin)->create())
            ->post(route('admin.votes.invalidate', $vote), ['reason' => 'Bot activity'])
            ->assertSessionHas('success');

        $this->assertSame(Vote::STATUS_INVALIDATED, $vote->fresh()->status);
        $this->assertSame(0, $nominee->fresh()->total_votes);
        $this->assertDatabaseHas('audit_logs', ['action' => 'votes.invalidated']);
    }

    public function test_event_creation_is_audited(): void
    {
        $admin = User::factory()->role(Role::Admin)->create();

        $this->actingAs($admin)->post(route('admin.events.store'), [
            'name' => 'Test Awards 2027',
            'status' => 'draft',
            'results_visibility' => 'hidden',
            'settings' => ['free_vote_scope' => 'category', 'free_voting_enabled' => '1'],
        ])->assertRedirect(route('admin.events.index'));

        $this->assertDatabaseHas('award_events', ['slug' => 'test-awards-2027']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'award_event.created', 'user_id' => $admin->id]);
    }
}
