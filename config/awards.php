<?php

/*
|--------------------------------------------------------------------------
| Awards platform configuration
|--------------------------------------------------------------------------
|
| Anything an awards administrator changes day to day (events, categories,
| packages, page content...) lives in the database. This file only holds
| platform-level defaults and the role -> permission map.
|
*/

return [

    'currency' => env('PAYSTACK_CURRENCY', 'NGN'),

    'currency_symbols' => [
        'NGN' => '₦',
        'GHS' => 'GH₵',
        'ZAR' => 'R',
        'KES' => 'KSh',
        'USD' => '$',
    ],

    // Defaults merged under each award event's `settings` column.
    'event_defaults' => [
        'free_voting_enabled' => true,
        'free_vote_scope' => 'category', // see App\Enums\FreeVoteScope
        'free_vote_requires_verification' => true,
        'paid_voting_enabled' => true,
        'paid_vote_requires_login' => false,
        'booking_enabled' => true,
    ],

    // Seconds the public leaderboard is cached for.
    'leaderboard_cache_ttl' => (int) env('LEADERBOARD_CACHE_TTL', 30),

    // Heuristics used by App\Services\FraudDetectionService. Flags are raised
    // for human review only; nothing is automatically removed.
    'fraud' => [
        'ip_free_votes_per_hour' => 15,
        'accounts_per_ip_per_day' => 5,
        'nominee_votes_spike_per_10_min' => 2000,
        'email_paid_orders_per_hour' => 10,
        'single_order_votes_threshold' => 5000,
        'failed_payments_per_email_per_hour' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Role-based access control
    |--------------------------------------------------------------------------
    |
    | Super administrators implicitly hold every permission (Gate::before).
    | Each permission becomes a Gate ability of the same name, so routes use
    | `->middleware('can:votes.adjust')` and code uses `$user->can(...)`.
    |
    */

    'permissions' => [
        'dashboard.view' => 'View the admin dashboard',
        'events.manage' => 'Manage award events and voting state',
        'categories.manage' => 'Manage categories',
        'nominees.manage' => 'Manage nominees',
        'packages.manage' => 'Manage voting packages',
        'votes.view' => 'View the vote ledger',
        'votes.adjust' => 'Create admin vote adjustments',
        'votes.invalidate' => 'Invalidate votes',
        'leaderboard.view' => 'View full results',
        'orders.view' => 'View vote orders',
        'orders.refund' => 'Refund orders',
        'payments.view' => 'View payments',
        'donations.view' => 'View donations',
        'bookings.view' => 'View event bookings',
        'booking_packages.manage' => 'Manage table/event packages',
        'voters.manage' => 'View and manage voter accounts',
        'fraud.manage' => 'Review fraud flags',
        'reports.voting' => 'View voting reports',
        'reports.financial' => 'View financial reports',
        'reports.voters' => 'View voter reports',
        'gallery.manage' => 'Manage gallery',
        'content.manage' => 'Manage pages, FAQs and home page',
        'contact.manage' => 'Manage contact messages',
        'audit.view' => 'View audit log',
        'settings.manage' => 'Manage site settings',
        'admins.manage' => 'Manage administrators and roles',
    ],

    'roles' => [
        'super_admin' => ['*'],

        'admin' => [
            'dashboard.view', 'events.manage', 'categories.manage', 'nominees.manage',
            'packages.manage', 'votes.view', 'votes.adjust', 'votes.invalidate', 'leaderboard.view',
            'orders.view', 'orders.refund', 'payments.view', 'donations.view', 'bookings.view',
            'booking_packages.manage', 'voters.manage', 'fraud.manage', 'reports.voting',
            'reports.financial', 'reports.voters', 'gallery.manage', 'content.manage',
            'contact.manage', 'audit.view',
        ],

        'finance' => [
            'dashboard.view', 'orders.view', 'orders.refund', 'payments.view', 'donations.view',
            'bookings.view', 'reports.financial',
        ],

        'voting_manager' => [
            'dashboard.view', 'categories.manage', 'nominees.manage', 'votes.view', 'votes.adjust',
            'votes.invalidate', 'leaderboard.view', 'fraud.manage', 'reports.voting',
        ],

        'voter' => [],
    ],
];
