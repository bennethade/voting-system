<?php

namespace App\Enums;

enum VoteType: string
{
    case Free = 'free';
    case Paid = 'paid';
    case AdminAdjustment = 'admin_adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Free',
            self::Paid => 'Paid',
            self::AdminAdjustment => 'Admin adjustment',
        };
    }
}
