<?php

namespace App\Enums;

enum ExerciseType: string
{
    case Reps = 'reps';
    case Timed = 'timed';

    public function targetLabel(int $target): string
    {
        return match ($this) {
            self::Reps => $target === 1 ? '1 rep' : "{$target} reps",
            self::Timed => $target === 1 ? '1 sec' : "{$target} sec",
        };
    }
}
