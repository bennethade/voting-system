<?php

namespace App\Enums;

enum ResultsVisibility: string
{
    case Hidden = 'hidden';
    case Live = 'live';
    case AfterVoting = 'after_voting';

    public function label(): string
    {
        return match ($this) {
            self::Hidden => 'Hidden',
            self::Live => 'Live (public vote counts)',
            self::AfterVoting => 'After voting closes',
        };
    }
}
