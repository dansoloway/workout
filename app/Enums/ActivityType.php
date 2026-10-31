<?php

namespace App\Enums;

enum ActivityType: string
{
    case Tennis = 'tennis';
    case Swimming = 'swimming';
    case Stretching = 'stretching';
    case Meditation = 'meditation';

    public function label(): string
    {
        return match ($this) {
            self::Tennis => 'Tennis',
            self::Swimming => 'Swimming',
            self::Stretching => 'Stretching',
            self::Meditation => 'Meditation',
        };
    }

    /**
     * Stretching and meditation are tracked apart from workouts.
     */
    public function isWorkout(): bool
    {
        return match ($this) {
            self::Tennis, self::Swimming => true,
            self::Stretching, self::Meditation => false,
        };
    }

    public function tracksMinutes(): bool
    {
        return $this !== self::Tennis;
    }

    public function minutesPrompt(): string
    {
        return match ($this) {
            self::Swimming => 'Enter how many minutes you swam.',
            self::Stretching => 'Enter how many minutes you stretched.',
            self::Meditation => 'Enter how many minutes you meditated.',
            self::Tennis => 'Enter the minutes.',
        };
    }

    /**
     * @return list<self>
     */
    public static function workouts(): array
    {
        return array_values(array_filter(self::cases(), fn (self $type) => $type->isWorkout()));
    }

    /**
     * @return list<self>
     */
    public static function practices(): array
    {
        return array_values(array_filter(self::cases(), fn (self $type) => ! $type->isWorkout()));
    }
}
