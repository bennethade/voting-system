<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('award_events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('short_description', 500)->nullable();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('banner')->nullable();
            $table->string('venue')->nullable();
            $table->date('start_date')->nullable();
            $table->dateTime('voting_start_at')->nullable();
            $table->dateTime('voting_end_at')->nullable();
            $table->dateTime('event_date')->nullable();
            $table->string('status', 16)->default('draft')->index();
            $table->string('results_visibility', 16)->default('hidden');
            $table->timestamp('results_locked_at')->nullable();
            $table->foreignId('results_locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_featured')->default(false)->index();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('award_event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 16)->default('active');
            $table->timestamps();

            // Category slugs only need to be unique within their event.
            $table->unique(['award_event_id', 'slug']);
            $table->index(['award_event_id', 'status', 'sort_order']);
        });

        Schema::create('nominees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('short_description', 500)->nullable();
            $table->text('bio')->nullable();
            $table->string('image')->nullable();
            $table->string('cover_image')->nullable();
            $table->json('social_links')->nullable();
            $table->string('website')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 16)->default('active');
            // Denormalised counter. The `votes` ledger is the source of truth;
            // see `php artisan votes:recalculate`.
            $table->bigInteger('total_votes')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['category_id', 'status', 'sort_order']);
            $table->index(['category_id', 'total_votes']);
        });

        Schema::create('voting_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('award_event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            // Amounts are stored in the currency's major unit with 2 decimals.
            $table->decimal('amount', 12, 2);
            $table->unsignedInteger('votes');
            $table->string('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 16)->default('active');
            $table->timestamps();

            $table->index(['award_event_id', 'status', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voting_packages');
        Schema::dropIfExists('nominees');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('award_events');
    }
};
