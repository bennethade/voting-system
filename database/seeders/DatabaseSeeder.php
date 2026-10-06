<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            StaffSeeder::class,
            ContentSeeder::class,
            AwardsDemoSeeder::class,
        ]);

        if (app()->environment('local')) {
            $this->call(DemoActivitySeeder::class);
        }
    }
}
