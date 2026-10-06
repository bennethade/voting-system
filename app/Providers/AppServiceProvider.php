<?php

namespace App\Providers;

use App\Models\AwardEvent;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Donation;
use App\Models\EventBooking;
use App\Models\EventPackage;
use App\Models\Faq;
use App\Models\FraudFlag;
use App\Models\GalleryItem;
use App\Models\Nominee;
use App\Models\Page;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vote;
use App\Models\VoteOrder;
use App\Models\VotingPackage;
use App\Services\HtmlSanitizer;
use App\Services\LeaderboardService;
use App\Services\SettingsService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsService::class);
        $this->app->singleton(HtmlSanitizer::class);
        $this->app->singleton(LeaderboardService::class);
    }

    public function boot(): void
    {
        // Short, stable type names in polymorphic columns instead of class names.
        Relation::enforceMorphMap([
            'user' => User::class,
            'award_event' => AwardEvent::class,
            'category' => Category::class,
            'nominee' => Nominee::class,
            'voting_package' => VotingPackage::class,
            'vote' => Vote::class,
            'vote_order' => VoteOrder::class,
            'donation' => Donation::class,
            'event_booking' => EventBooking::class,
            'event_package' => EventPackage::class,
            'payment' => Payment::class,
            'fraud_flag' => FraudFlag::class,
            'gallery_item' => GalleryItem::class,
            'page' => Page::class,
            'faq' => Faq::class,
            'contact_message' => ContactMessage::class,
            'setting' => Setting::class,
        ]);

        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(8)->letters()->mixedCase()->numbers()->uncompromised()
            : Password::min(8)->letters()->numbers());

        Paginator::defaultView('components.pagination');

        $this->registerGates();
        $this->registerRateLimiters();
    }

    private function registerGates(): void
    {
        Gate::before(function (User $user) {
            if (! $user->isActive()) {
                return false;
            }

            return $user->isSuperAdmin() ? true : null;
        });

        foreach (array_keys(config('awards.permissions', [])) as $permission) {
            Gate::define($permission, fn (User $user) => $user->hasPermission($permission));
        }

        Gate::define('access-admin', fn (User $user) => $user->isStaff());
    }

    private function registerRateLimiters(): void
    {
        $byUserOrIp = fn (Request $request) => $request->user()?->id ?: $request->ip();

        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(20)->by($request->ip()),
        ]);

        RateLimiter::for('register', fn (Request $request) => Limit::perHour(10)->by($request->ip()));

        RateLimiter::for('password-reset', fn (Request $request) => [
            Limit::perMinute(3)->by(strtolower((string) $request->input('email'))),
            Limit::perHour(20)->by($request->ip()),
        ]);

        RateLimiter::for('verification', fn (Request $request) => Limit::perMinute(3)->by($byUserOrIp($request)));

        RateLimiter::for('free-vote', fn (Request $request) => [
            Limit::perMinute(10)->by('u'.$byUserOrIp($request)),
            Limit::perMinute(30)->by('ip'.$request->ip()),
        ]);

        RateLimiter::for('payment', fn (Request $request) => [
            Limit::perMinute(10)->by('u'.$byUserOrIp($request)),
            Limit::perHour(60)->by('ip'.$request->ip()),
        ]);

        RateLimiter::for('contact', fn (Request $request) => [
            Limit::perMinute(2)->by($request->ip()),
            Limit::perDay(20)->by($request->ip()),
        ]);

        RateLimiter::for('webhooks', fn (Request $request) => Limit::perMinute(300)->by($request->ip()));
    }
}
