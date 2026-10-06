<?php

namespace App\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case Upcoming = 'upcoming';
    case Voting = 'voting';
    case Paused = 'paused';
    case Closed = 'closed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** Statuses visible on the public site. */
    public static function public(): array
    {
        return [self::Upcoming, self::Voting, self::Paused, self::Closed, self::Completed];
    }
}
