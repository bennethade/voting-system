<?php

namespace Database\Factories;

use App\Models\AwardEvent;
use App\Models\VotingPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VotingPackage>
 */
class VotingPackageFactory extends Factory
{
    public function definition(): array
    {
        $votes = fake()->randomElement([1, 5, 10, 50, 100]);

        return [
            'award_event_id' => AwardEvent::factory(),
            'name' => "{$votes} votes",
            'amount' => $votes * 100,
            'votes' => $votes,
            'sort_order' => 0,
            'status' => 'active',
        ];
    }
}
