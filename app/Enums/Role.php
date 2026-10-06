<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Finance = 'finance';
    case VotingManager = 'voting_manager';
    case Voter = 'voter';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Administrator',
            self::Admin => 'Administrator',
            self::Finance => 'Finance',
            self::VotingManager => 'Voting Manager',
            self::Voter => 'Voter',
        };
    }

    public function isStaff(): bool
    {
        return $this !== self::Voter;
    }

    /** @return array<int, self> */
    public static function staff(): array
    {
        return array_values(array_filter(self::cases(), fn (self $r) => $r->isStaff()));
    }
}
