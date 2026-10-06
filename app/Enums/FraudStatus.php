<?php

namespace App\Enums;

enum FraudStatus: string
{
    case Open = 'open';
    case Reviewing = 'reviewing';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Invalidated = 'invalidated';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Reviewing => 'Under review',
            self::Approved => 'Approved (legitimate)',
            self::Rejected => 'Rejected (dismissed)',
            self::Invalidated => 'Votes invalidated',
        };
    }
}
