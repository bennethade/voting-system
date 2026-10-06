<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Enums\ResultsVisibility;
use App\Models\AwardEvent;
use App\Models\Category;
use App\Models\EventPackage;
use App\Models\Nominee;
use App\Models\VotingPackage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * A sample event with categories, packages and clearly fictional nominees.
 * Nothing here is hard-coded into the application; it is all editable data.
 */
class AwardsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $event = AwardEvent::firstOrCreate(['slug' => 'magnified-influencer-awards-2026'], [
            'name' => 'Magnified Influencer Awards 2026',
            'short_description' => 'Celebrating the creators, comedians and entrepreneurs who defined online culture this year.',
            'description' => "The 2026 edition brings together the most influential digital creators for a season of public voting and a glittering awards night.\n\nVote free every day in each category, or power up your favourite with a vote package.",
            'venue' => 'Grand Lagoon Hall, Lagos',
            'start_date' => now()->startOfMonth(),
            'voting_start_at' => now()->subDays(10)->startOfDay(),
            'voting_end_at' => now()->addDays(45)->setTime(23, 59),
            'event_date' => now()->addDays(60)->setTime(18, 0),
            'status' => EventStatus::Voting,
            'results_visibility' => ResultsVisibility::Live,
            'is_featured' => true,
            'settings' => array_merge(config('awards.event_defaults'), ['free_vote_scope' => 'category_daily']),
        ]);

        // Fictional sample nominees only.
        $categories = [
            'Best Influencer Actor' => ['Tobi Adeyemi-Kane', 'Marcus Oduya', 'Ifeanyi Brooks', 'Kelechi Stone'],
            'Best Influencer Actress' => ['Amara Lightfoot', 'Zainab Rivers', 'Nneka Holloway', 'Simi Castellan'],
            'Best Influencer Comedian' => ['Laffy Dee', 'Chuks the Jester', 'Mama Giggles', 'Uncle Punchline'],
            'Best Content Creator' => ['Pixel Ade', 'Ronke Creates', 'Vlog With Victor', 'Studio Temi'],
            'Best Influencer Entrepreneur' => ['Kemi Ventures', 'Dayo Builds', 'Hustle With Hadiza', 'Founder Femi'],
        ];

        $order = 0;
        foreach ($categories as $name => $nominees) {
            $category = Category::firstOrCreate(
                ['award_event_id' => $event->id, 'slug' => Str::slug($name)],
                ['name' => $name, 'description' => "Who gave us the most memorable moments as {$name}? You decide.", 'sort_order' => $order++, 'status' => 'active'],
            );

            foreach ($nominees as $i => $nomineeName) {
                Nominee::firstOrCreate(['slug' => Str::slug($nomineeName)], [
                    'category_id' => $category->id,
                    'name' => $nomineeName,
                    'short_description' => 'A fictional sample nominee for '.Str::lower($name).'.',
                    'bio' => "{$nomineeName} is a sample nominee created for demonstration purposes. Replace this biography from the admin panel with the real nominee's story, achievements and highlights from the year.",
                    'social_links' => ['instagram' => 'https://instagram.com/'.Str::slug($nomineeName, ''), 'x' => null, 'tiktok' => 'https://www.tiktok.com/@'.Str::slug($nomineeName, '')],
                    'sort_order' => $i,
                    'status' => 'active',
                ]);
            }
        }

        $packages = [[100, 1], [200, 2], [500, 5], [1000, 10], [5000, 50], [10000, 100], [60000, 600]];
        foreach ($packages as $i => [$amount, $votes]) {
            VotingPackage::firstOrCreate(
                ['award_event_id' => $event->id, 'votes' => $votes],
                ['name' => $votes === 1 ? '1 vote' : "{$votes} votes", 'amount' => $amount, 'sort_order' => $i, 'status' => 'active'],
            );
        }

        $tables = [
            ['Royal Table', 2500000, 10, ['Front-row stage view', 'Premium champagne service', 'Red-carpet photo session', 'Dedicated host']],
            ['VIP Table', 1500000, 10, ['Prime stage view', 'Premium drinks package', 'VIP lounge access']],
            ['Gold Table', 1000000, 10, ['Great stage view', 'Drinks package', 'Dinner service']],
            ['Silver Table', 600000, 8, ['Dinner service', 'Drinks on arrival']],
            ['Standard Seat', 50000, 1, ['Reserved seat', 'Dinner service']],
        ];
        foreach ($tables as $i => [$name, $price, $seats, $benefits]) {
            EventPackage::firstOrCreate(
                ['award_event_id' => $event->id, 'name' => $name],
                ['price' => $price, 'seats' => $seats, 'benefits' => $benefits, 'description' => "{$seats} ".Str::plural('seat', $seats).' at the awards night.', 'quantity_available' => $seats === 1 ? 200 : 10, 'sort_order' => $i, 'status' => 'active'],
            );
        }
    }
}
