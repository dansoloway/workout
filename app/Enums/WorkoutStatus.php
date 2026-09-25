<?php

namespace App\Enums;

enum WorkoutStatus: string
{
    case Pending = 'pending';
    case Partial = 'partial';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Not started',
            self::Partial => 'Partially completed',
            self::Completed => 'Workout complete ✓',
        };
    }
}
