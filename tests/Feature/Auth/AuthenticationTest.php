<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use App\Notifications\Auth\ResetPasswordNotification;
use App\Notifications\Auth\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_a_voter_with_hashed_password_and_sends_verification(): void
    {
        Notification::fake();

        $response = $this->post(route('register.store'), [
            'name' => 'Jane Voter',
            'email' => 'Jane@Example.com',
            'password' => 'secret123A',
            'password_confirmation' => 'secret123A',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $user = User::where('email', 'jane@example.com')->firstOrFail();

        $this->assertSame(Role::Voter, $user->role);
        $this->assertNotSame('secret123A', $user->getRawOriginal('password'));
        $this->assertTrue(Hash::check('secret123A', $user->password));
        $this->assertNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_registration_cannot_set_role_through_mass_assignment(): void
    {
        Notification::fake();

        $this->post(route('register.store'), [
            'name' => 'Sneaky',
            'email' => 'sneaky@example.com',
            'password' => 'secret123A',
            'password_confirmation' => 'secret123A',
            'terms' => '1',
            'role' => 'super_admin',
        ]);

        $this->assertSame(Role::Voter, User::where('email', 'sneaky@example.com')->first()->role);
    }

    public function test_registration_honeypot_rejects_bots(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Bot', 'email' => 'bot@example.com', 'password' => 'secret123A',
            'password_confirmation' => 'secret123A', 'terms' => '1', 'website' => 'http://spam.test',
        ])->assertSessionHasErrors('website');

        $this->assertDatabaseMissing('users', ['email' => 'bot@example.com']);
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->create(['password' => 'secret123A']);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'secret123A'])
            ->assertRedirect(route('voter.dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);

        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create(['password' => 'secret123A']);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_deactivated_user_cannot_login(): void
    {
        $user = User::factory()->inactive()->create(['password' => 'secret123A']);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'secret123A'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        $user = User::factory()->create(['password' => 'secret123A']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), ['email' => $user->email, 'password' => 'nope']);
        }

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'secret123A'])->assertStatus(429);
    }

    public function test_admin_login_rejects_voters_and_accepts_staff(): void
    {
        $voter = User::factory()->create(['password' => 'secret123A']);
        $admin = User::factory()->role(Role::Admin)->create(['password' => 'secret123A']);

        $this->post(route('admin.login.store'), ['email' => $voter->email, 'password' => 'secret123A'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post(route('admin.login.store'), ['email' => $admin->email, 'password' => 'secret123A'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_password_reset_flow(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('status');

        $token = null;
        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($n) use (&$token) {
            $token = $n->token;

            return true;
        });

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))->assertOk();

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'brandNew123',
            'password_confirmation' => 'brandNew123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('brandNew123', $user->fresh()->password));
    }

    public function test_password_reset_does_not_reveal_unknown_emails(): void
    {
        $this->post(route('password.email'), ['email' => 'nobody@example.com'])
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();
    }

    public function test_password_reset_rejects_invalid_token(): void
    {
        $user = User::factory()->create();

        $this->post(route('password.update'), [
            'token' => 'invalid', 'email' => $user->email,
            'password' => 'brandNew123', 'password_confirmation' => 'brandNew123',
        ])->assertSessionHasErrors('email');
    }

    public function test_email_verification_with_signed_link(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect(route('voter.dashboard'));
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_email_verification_rejects_tampered_links(): void
    {
        $user = User::factory()->unverified()->create();

        $unsigned = route('verification.verify', ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->actingAs($user)->get($unsigned)->assertForbidden();

        $wrongHash = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1('other@example.com')]);
        $this->actingAs($user)->get($wrongHash)->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_voter_area_requires_login(): void
    {
        $this->get(route('voter.dashboard'))->assertRedirect(route('login'));
    }

    public function test_deactivated_user_is_logged_out_on_next_request(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $user->forceFill(['status' => User::STATUS_SUSPENDED])->save();

        $this->get(route('voter.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
