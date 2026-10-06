<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates one account per staff role. Set SEED_ADMIN_PASSWORD in .env to
 * choose the password; change it immediately on any shared environment.
 */
class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('SEED_ADMIN_PASSWORD', 'ChangeMe!2026');

        $accounts = [
            ['Super Admin', 'admin@example.com', Role::SuperAdmin],
            ['Ada Administrator', 'manager@example.com', Role::Admin],
            ['Femi Finance', 'finance@example.com', Role::Finance],
            ['Vera Voting', 'voting@example.com', Role::VotingManager],
        ];

        foreach ($accounts as [$name, $email, $role]) {
            $user = User::firstOrNew(['email' => $email]);
            $user->fill(['name' => $name, 'password' => $password]);
            $user->forceFill(['role' => $role, 'status' => User::STATUS_ACTIVE, 'email_verified_at' => now()])->save();
        }

        $this->command?->warn("Staff accounts seeded (password: {$password}). Change these passwords before going live.");
    }
}
