<?php

namespace Database\Seeders;

use App\Enums\ExerciseType;
use App\Enums\RoutineKind;
use App\Models\Exercise;
use App\Models\Routine;
use Illuminate\Database\Seeder;

class MorningWorkoutSeeder extends Seeder
{
    public function run(): void
    {
        $routine = Routine::query()
            ->where('name', 'Morning Workout')
            ->where('kind', RoutineKind::Workout)
            ->whereNull('user_id')
            ->first() ?? new Routine([
                'name' => 'Morning Workout',
                'kind' => RoutineKind::Workout,
            ]);

        $routine->fill([
            'user_id' => null,
            'rounds' => 2,
            'rest_seconds' => 60,
            'is_current' => true,
            'active' => true,
        ]);
        $routine->save();

        $definitions = [
            ['name' => 'Cat–cow', 'type' => ExerciseType::Reps, 'default_target' => 8],
            ['name' => 'Bodyweight squats', 'type' => ExerciseType::Reps, 'default_target' => 8],
            ['name' => 'Knee push-ups', 'type' => ExerciseType::Reps, 'default_target' => 3],
            ['name' => 'Plank', 'type' => ExerciseType::Timed, 'default_target' => 15],
            ['name' => 'Sit-ups', 'type' => ExerciseType::Reps, 'default_target' => 5],
        ];

        foreach ($definitions as $index => $definition) {
            $exercise = Exercise::query()
                ->where('routine_id', $routine->id)
                ->where('name', $definition['name'])
                ->first() ?? new Exercise([
                    'routine_id' => $routine->id,
                    'name' => $definition['name'],
                ]);

            $exercise->fill([
                'user_id' => null,
                'type' => $definition['type'],
                'default_target' => $definition['default_target'],
                'sort_order' => $index + 1,
                'active' => true,
            ]);
            $exercise->save();
        }
    }
}
