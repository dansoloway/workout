<?php

namespace Database\Factories;

use App\Enums\ExerciseType;
use App\Models\Exercise;
use App\Models\Routine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exercise>
 */
class ExerciseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'routine_id' => Routine::factory(),
            'name' => fake()->words(2, true),
            'type' => ExerciseType::Reps,
            'default_target' => 8,
            'sort_order' => 1,
            'active' => true,
        ];
    }
}
