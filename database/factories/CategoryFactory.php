<?php

namespace Database\Factories;

use App\Models\AwardEvent;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = 'Best '.Str::title(fake()->unique()->words(2, true));

        return [
            'award_event_id' => AwardEvent::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'sort_order' => 0,
            'status' => 'active',
        ];
    }
}
