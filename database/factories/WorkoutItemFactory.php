<?php

namespace Database\Factories;

use App\Enums\ExerciseType;
use App\Models\Workout;
use App\Models\WorkoutItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkoutItem>
 */
class WorkoutItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workout_id' => Workout::factory(),
            'exercise_id' => null,
            'round_number' => 1,
            'sort_order' => 1,
            'name' => 'Cat–cow',
            'type' => ExerciseType::Reps,
            'target' => 8,
            'completed' => false,
            'completed_at' => null,
        ];
    }
}
