<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\FreeVoteController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\NomineeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\VoteCheckoutController;
use App\Http\Controllers\Voter\DashboardController as VoterDashboardController;
use App\Http\Controllers\Voter\ProfileController;
use App\Http\Controllers\VotingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website
|--------------------------------------------------------------------------
*/

Route::get('/', HomeController::class)->name('home');

Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');

Route::get('/voting', [VotingController::class, 'index'])->name('voting.index');
Route::get('/voting/{event}', [VotingController::class, 'event'])->name('voting.event');
Route::get('/voting/{event}/{category:slug}', [VotingController::class, 'category'])
    ->scopeBindings()
    ->name('voting.category');

Route::get('/nominees/{nominee}', [NomineeController::class, 'show'])->name('nominees.show');

Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard');
Route::get('/results', [LeaderboardController::class, 'index'])->name('results');

Route::get('/gallery', [GalleryController::class, 'index'])->name('gallery');
Route::get('/faq', [FaqController::class, 'index'])->name('faq');

Route::get('/contact', [ContactController::class, 'create'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:contact')->name('contact.store');

Route::get('/about', [PageController::class, 'show'])->defaults('slug', 'about')->name('about');
Route::get('/terms', [PageController::class, 'show'])->defaults('slug', 'terms')->name('terms');
Route::get('/privacy', [PageController::class, 'show'])->defaults('slug', 'privacy')->name('privacy');
Route::get('/refund-policy', [PageController::class, 'show'])->defaults('slug', 'refund-policy')->name('refund-policy');
Route::get('/pages/{slug}', [PageController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('pages.show');

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

/*
|--------------------------------------------------------------------------
| Voting, checkout and payments
|--------------------------------------------------------------------------
|
| Guests may buy votes, donate and book (if the event allows it), so the
| review/status/receipt pages for those are protected by signed URLs
| rather than by login.
|
*/

Route::post('/nominees/{nominee}/free-vote', [FreeVoteController::class, 'store'])
    ->middleware(['auth', 'throttle:free-vote'])
    ->name('votes.free');

Route::get('/nominees/{nominee}/buy-votes', [VoteCheckoutController::class, 'create'])->name('votes.checkout');
Route::post('/nominees/{nominee}/buy-votes', [VoteCheckoutController::class, 'store'])
    ->middleware('throttle:payment')
    ->name('votes.checkout.store');
Route::get('/checkout/votes/{voteOrder}', [VoteCheckoutController::class, 'show'])
    ->middleware('signed')
    ->name('votes.checkout.review');

Route::get('/donate', [DonationController::class, 'create'])->name('donate');
Route::post('/donate', [DonationController::class, 'store'])->middleware('throttle:payment')->name('donate.store');
Route::get('/checkout/donations/{donation}', [DonationController::class, 'show'])
    ->middleware('signed')
    ->name('donate.review');

Route::get('/book-a-table', [BookingController::class, 'index'])->name('bookings.index');
Route::get('/book-a-table/{eventPackage}', [BookingController::class, 'create'])->name('bookings.create');
Route::post('/book-a-table/{eventPackage}', [BookingController::class, 'store'])->middleware('throttle:payment')->name('bookings.store');
Route::get('/checkout/bookings/{eventBooking}', [BookingController::class, 'show'])
    ->middleware('signed')
    ->name('bookings.review');

// Explicit confirmation step: only this POST ever starts a gateway payment.
Route::post('/checkout/{type}/{reference}/pay', [CheckoutController::class, 'pay'])
    ->whereIn('type', ['vote_order', 'donation', 'event_booking'])
    ->middleware(['signed', 'throttle:payment'])
    ->name('checkout.pay');

Route::get('/payment/callback', [PaymentController::class, 'callback'])->name('payment.callback');
Route::get('/payment/status/{payment:reference}', [PaymentController::class, 'status'])
    ->middleware('signed')
    ->name('payment.status');
Route::get('/payment/status/{payment:reference}/check', [PaymentController::class, 'check'])
    ->middleware(['signed', 'throttle:60,1'])
    ->name('payment.check');

Route::get('/receipts/votes/{voteOrder}', [ReceiptController::class, 'vote'])->name('receipts.vote');
Route::get('/receipts/bookings/{eventBooking}', [ReceiptController::class, 'booking'])->name('receipts.booking');

/*
|--------------------------------------------------------------------------
| Voter authentication (custom; no starter kit)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login')->name('login.store');

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:register')->name('register.store');

    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:password-reset')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:password-reset')->name('password.update');
});

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->whereNumber('id')
    ->name('verification.verify');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:verification')
        ->name('verification.send');

    /*
    | Voter area
    */
    Route::prefix('voter')->name('voter.')->group(function () {
        Route::get('/dashboard', [VoterDashboardController::class, 'index'])->name('dashboard');
        Route::get('/votes', [VoterDashboardController::class, 'votes'])->name('votes');
        Route::get('/orders', [VoterDashboardController::class, 'orders'])->name('orders');
        Route::get('/payments', [VoterDashboardController::class, 'payments'])->name('payments');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');
    });
});
