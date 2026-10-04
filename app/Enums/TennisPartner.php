<?php

namespace App\Enums;

enum TennisPartner: string
{
    case Osnat = 'osnat';
    case Shalev = 'shalev';
    case Wal = 'wal';

    public function label(): string
    {
        return match ($this) {
            self::Osnat => 'Osnat',
            self::Shalev => 'Shalev',
            self::Wal => 'Wal',
        };
    }
}
