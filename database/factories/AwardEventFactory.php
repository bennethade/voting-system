<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Enums\ResultsVisibility;
use App\Models\AwardEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AwardEvent>
 */
class AwardEventFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true).' Awards '.now()->year;

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraphs(2, true),
            'venue' => fake()->city().' Convention Centre',
            'voting_start_at' => now()->subDay(),
            'voting_end_at' => now()->addMonth(),
            'event_date' => now()->addMonths(2),
            'status' => EventStatus::Voting,
            'results_visibility' => ResultsVisibility::Live,
            'settings' => config('awards.event_defaults'),
        ];
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'status' => EventStatus::Closed,
            'voting_start_at' => now()->subMonth(),
            'voting_end_at' => now()->subDay(),
        ]);
    }

    /** @param array<string, mixed> $settings */
    public function withSettings(array $settings): static
    {
        return $this->state(fn () => ['settings' => array_merge(config('awards.event_defaults'), $settings)]);
    }
}
