<?php

namespace Database\Factories;

use App\Enums\RoutineKind;
use App\Enums\WorkoutStatus;
use App\Models\Routine;
use App\Models\Workout;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workout>
 */
class WorkoutFactory extends Factory
{
    public function definition(): array
    {
        return [
            'routine_id' => Routine::factory(),
            'routine_name' => 'Morning Workout',
            'kind' => RoutineKind::Workout,
            'workout_date' => today()->toDateString(),
            'rounds' => 2,
            'rest_seconds' => 60,
            'status' => WorkoutStatus::Pending,
        ];
    }
}
