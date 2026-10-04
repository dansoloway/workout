<?php

namespace App\Enums;

enum ActivityType: string
{
    case Tennis = 'tennis';
    case Swimming = 'swimming';

    public function label(): string
    {
        return match ($this) {
            self::Tennis => 'Tennis',
            self::Swimming => 'Swimming',
        };
    }
}
