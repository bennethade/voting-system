<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vote_orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->foreignId('award_event_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('nominee_id')->constrained()->restrictOnDelete();
            $table->foreignId('voting_package_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('voter_name')->nullable();
            $table->string('voter_email');
            $table->string('voter_phone', 32)->nullable();
            // Snapshot of the package at purchase time so later package edits
            // never change what was bought.
            $table->decimal('amount', 12, 2);
            $table->unsignedInteger('votes');
            $table->string('currency', 3)->default('NGN');
            $table->string('payment_provider', 20)->default('paystack');
            $table->string('payment_reference', 64)->nullable()->index();
            $table->string('status', 16)->default('pending')->index();
            $table->timestamp('paid_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['award_event_id', 'status', 'created_at']);
            $table->index(['nominee_id', 'status']);
            $table->index('created_at');
        });

        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('award_event_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('nominee_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // unique: one order can only ever be credited once.
            $table->foreignId('vote_order_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->string('vote_type', 20)->index(); // free | paid | admin_adjustment
            // Signed so admin adjustments can subtract.
            $table->integer('votes');
            $table->string('status', 16)->default('valid')->index(); // valid | invalidated
            // Encodes the free-vote rule (e.g. "e1:u5:c3"). NULL for non-free
            // votes; the unique index enforces the rule at the database level.
            $table->string('free_vote_key', 120)->nullable()->unique();
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->string('ip_address', 45)->nullable()->index();
            $table->string('user_agent', 512)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('invalidated_at')->nullable();
            $table->foreignId('invalidated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['nominee_id', 'status']);
            $table->index(['award_event_id', 'vote_type', 'created_at']);
            $table->index(['category_id', 'status']);
            $table->index(['user_id', 'created_at']);
            $table->index('created_at');
        });

        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('award_event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('donor_name');
            $table->string('email');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('NGN');
            $table->string('payment_reference', 64)->nullable()->index();
            $table->string('status', 16)->default('pending')->index();
            $table->text('message')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('event_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('award_event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 12, 2);
            $table->unsignedInteger('seats')->default(1);
            $table->text('description')->nullable();
            $table->json('benefits')->nullable();
            $table->unsignedInteger('quantity_available')->default(0);
            $table->unsignedInteger('quantity_sold')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 16)->default('active');
            $table->timestamps();

            $table->index(['award_event_id', 'status', 'sort_order']);
        });

        Schema::create('event_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->foreignId('award_event_id')->constrained()->restrictOnDelete();
            $table->foreignId('event_package_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('contact_name');
            $table->string('contact_email');
            $table->string('contact_phone', 32)->nullable();
            $table->string('organization')->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('seats');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('NGN');
            $table->json('attendees')->nullable();
            $table->text('notes')->nullable();
            $table->string('payment_reference', 64)->nullable()->index();
            $table->string('status', 16)->default('pending')->index();
            $table->timestamp('paid_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });

        // One row per payment attempt with the gateway. Orders, donations and
        // bookings are all "payables"; a payable may have several attempts.
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 64)->unique();
            $table->morphs('payable');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 20)->default('paystack');
            $table->string('provider_transaction_id', 64)->nullable()->index();
            $table->string('email');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('NGN');
            $table->string('status', 16)->default('pending')->index(); // pending|success|failed|abandoned|refunded
            $table->string('channel', 32)->nullable();
            $table->string('gateway_response')->nullable();
            $table->string('verified_via', 16)->nullable(); // webhook|callback|manual
            $table->string('authorization_url', 512)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('payment_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 20)->default('paystack');
            $table->string('event', 64)->nullable();
            $table->string('reference', 64)->nullable()->index();
            $table->boolean('signature_valid')->default(false);
            $table->string('result', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_logs');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('event_bookings');
        Schema::dropIfExists('event_packages');
        Schema::dropIfExists('donations');
        Schema::dropIfExists('votes');
        Schema::dropIfExists('vote_orders');
    }
};
