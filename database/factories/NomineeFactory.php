<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Nominee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Nominee>
 */
class NomineeFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->name();

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'short_description' => fake()->sentence(),
            'bio' => fake()->paragraphs(2, true),
            'social_links' => ['instagram' => 'https://instagram.com/'.Str::slug($name, '')],
            'sort_order' => 0,
            'status' => 'active',
            'total_votes' => 0,
        ];
    }
}
