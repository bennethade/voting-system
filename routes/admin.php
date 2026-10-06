<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Admin\Auth\AdminLoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin routes  (prefix: /admin, name: admin.)
|--------------------------------------------------------------------------
|
| Every route past the login screens requires a signed-in, active staff
| account ('staff') AND the specific permission ('can:...'). Hiding links in
| Blade is cosmetic only; these middleware are the real enforcement.
|
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [AdminLoginController::class, 'create'])->name('login');
    Route::post('/login', [AdminLoginController::class, 'store'])->middleware('throttle:login')->name('login.store');

    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->defaults('area', 'admin')->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->defaults('area', 'admin')->middleware('throttle:password-reset')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->defaults('area', 'admin')->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->defaults('area', 'admin')->middleware('throttle:password-reset')->name('password.update');
});

Route::middleware('staff')->group(function () {
    Route::post('/logout', [AdminLoginController::class, 'destroy'])->name('logout');

    Route::get('/', [Admin\DashboardController::class, 'index'])->middleware('can:dashboard.view')->name('dashboard');

    // Award events and voting state
    Route::middleware('can:events.manage')->group(function () {
        Route::resource('events', Admin\AwardEventController::class)->except('show');
        Route::post('events/{event}/status', [Admin\AwardEventController::class, 'transition'])->name('events.transition');
        Route::post('events/{event}/lock-results', [Admin\AwardEventController::class, 'lockResults'])->name('events.lock');
        Route::post('events/{event}/unlock-results', [Admin\AwardEventController::class, 'unlockResults'])->name('events.unlock');
    });

    Route::resource('categories', Admin\CategoryController::class)->except('show')->middleware('can:categories.manage');
    Route::resource('nominees', Admin\NomineeController::class)->except('show')->middleware('can:nominees.manage');

    Route::middleware('can:packages.manage')->group(function () {
        Route::resource('voting-packages', Admin\VotingPackageController::class)->except('show')->parameters(['voting-packages' => 'votingPackage']);
        Route::post('voting-packages/reorder', [Admin\VotingPackageController::class, 'reorder'])->name('voting-packages.reorder');
    });

    // Vote ledger
    Route::get('votes', [Admin\VoteController::class, 'index'])->middleware('can:votes.view')->name('votes.index');
    Route::get('votes/export', [Admin\VoteController::class, 'export'])->middleware('can:votes.view')->name('votes.export');
    Route::get('votes/adjust', [Admin\VoteController::class, 'adjustForm'])->middleware('can:votes.adjust')->name('votes.adjust');
    Route::post('votes/adjust', [Admin\VoteController::class, 'adjust'])->middleware('can:votes.adjust')->name('votes.adjust.store');
    Route::post('votes/{vote}/invalidate', [Admin\VoteController::class, 'invalidate'])->middleware('can:votes.invalidate')->name('votes.invalidate');

    Route::get('leaderboard', [Admin\LeaderboardController::class, 'index'])->middleware('can:leaderboard.view')->name('leaderboard');

    // Money
    Route::middleware('can:orders.view')->group(function () {
        Route::get('orders', [Admin\VoteOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/export', [Admin\VoteOrderController::class, 'export'])->name('orders.export');
        Route::get('orders/{voteOrder}', [Admin\VoteOrderController::class, 'show'])->name('orders.show');
    });

    Route::middleware('can:payments.view')->group(function () {
        Route::get('payments', [Admin\PaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/export', [Admin\PaymentController::class, 'export'])->name('payments.export');
        Route::get('payments/{payment}', [Admin\PaymentController::class, 'show'])->name('payments.show');
        Route::post('payments/{payment}/verify', [Admin\PaymentController::class, 'verify'])->name('payments.verify');
    });
    Route::post('payments/{payment}/refund', [Admin\PaymentController::class, 'refund'])->middleware('can:orders.refund')->name('payments.refund');

    Route::middleware('can:donations.view')->group(function () {
        Route::get('donations', [Admin\DonationController::class, 'index'])->name('donations.index');
        Route::get('donations/export', [Admin\DonationController::class, 'export'])->name('donations.export');
        Route::get('donations/{donation}', [Admin\DonationController::class, 'show'])->name('donations.show');
    });

    Route::middleware('can:bookings.view')->group(function () {
        Route::get('bookings', [Admin\EventBookingController::class, 'index'])->name('bookings.index');
        Route::get('bookings/export', [Admin\EventBookingController::class, 'export'])->name('bookings.export');
        Route::get('bookings/{eventBooking}', [Admin\EventBookingController::class, 'show'])->name('bookings.show');
    });

    Route::resource('event-packages', Admin\EventPackageController::class)->except('show')
        ->parameters(['event-packages' => 'eventPackage'])
        ->middleware('can:booking_packages.manage');

    // People
    Route::middleware('can:voters.manage')->group(function () {
        Route::get('voters', [Admin\VoterController::class, 'index'])->name('voters.index');
        Route::get('voters/{voter}', [Admin\VoterController::class, 'show'])->name('voters.show');
        Route::post('voters/{voter}/status', [Admin\VoterController::class, 'updateStatus'])->name('voters.status');
    });

    Route::resource('administrators', Admin\AdministratorController::class)->except('show')
        ->parameters(['administrators' => 'administrator'])
        ->middleware('can:admins.manage');

    // Integrity
    Route::middleware('can:fraud.manage')->group(function () {
        Route::get('fraud', [Admin\FraudController::class, 'index'])->name('fraud.index');
        Route::post('fraud/scan', [Admin\FraudController::class, 'scan'])->name('fraud.scan');
        Route::get('fraud/{fraudFlag}', [Admin\FraudController::class, 'show'])->name('fraud.show');
        Route::post('fraud/{fraudFlag}/resolve', [Admin\FraudController::class, 'resolve'])->name('fraud.resolve');
    });

    Route::get('audit-logs', [Admin\AuditLogController::class, 'index'])->middleware('can:audit.view')->name('audit-logs.index');

    // Reports (each report checks its own permission in the controller)
    Route::get('reports', [Admin\ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/{report}/export', [Admin\ReportController::class, 'export'])
        ->whereIn('report', ['votes-by-nominee', 'votes-by-category', 'revenue-daily', 'voters'])
        ->name('reports.export');

    // Content
    Route::resource('gallery', Admin\GalleryController::class)->except('show')
        ->parameters(['gallery' => 'galleryItem'])
        ->middleware('can:gallery.manage');

    Route::middleware('can:content.manage')->group(function () {
        Route::resource('pages', Admin\PageController::class)->except('show');
        Route::resource('faqs', Admin\FaqController::class)->except('show');
    });

    Route::middleware('can:contact.manage')->group(function () {
        Route::get('messages', [Admin\ContactMessageController::class, 'index'])->name('messages.index');
        Route::get('messages/{contactMessage}', [Admin\ContactMessageController::class, 'show'])->name('messages.show');
        Route::post('messages/{contactMessage}/reply', [Admin\ContactMessageController::class, 'reply'])->name('messages.reply');
        Route::post('messages/{contactMessage}/status', [Admin\ContactMessageController::class, 'updateStatus'])->name('messages.status');
    });

    Route::get('settings', [Admin\SettingController::class, 'edit'])->middleware('can:settings.manage')->name('settings.edit');
    Route::put('settings', [Admin\SettingController::class, 'update'])->middleware('can:settings.manage')->name('settings.update');
});
