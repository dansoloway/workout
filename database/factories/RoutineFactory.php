<?php

namespace Database\Factories;

use App\Enums\RoutineKind;
use App\Models\Routine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Routine>
 */
class RoutineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Morning Workout',
            'kind' => RoutineKind::Workout,
            'rounds' => 2,
            'rest_seconds' => 60,
            'is_current' => true,
            'active' => true,
        ];
    }
}
