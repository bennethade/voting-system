<?php

namespace App\Enums;

enum FreeVoteScope: string
{
    case Event = 'event';
    case EventDaily = 'event_daily';
    case Category = 'category';
    case CategoryDaily = 'category_daily';

    public function label(): string
    {
        return match ($this) {
            self::Event => 'One free vote per voter for the whole event',
            self::EventDaily => 'One free vote per voter per day for the event',
            self::Category => 'One free vote per voter per category',
            self::CategoryDaily => 'One free vote per voter per category per day',
        };
    }
}
