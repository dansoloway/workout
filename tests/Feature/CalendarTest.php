<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Enums\WorkoutStatus;
use App\Models\Exercise;
use App\Models\Routine;
use App\Models\Workout;
use App\Models\WorkoutItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_distinguishes_completed_partial_and_empty_days(): void
    {
        $routine = Routine::factory()->create(['name' => 'Morning Workout']);
        $month = today()->startOfMonth();
        $completedDate = $month->copy();
        $partialDate = $month->copy()->addDay();
        $pendingDate = $month->copy()->addDays(2);
        $emptyDate = $month->copy()->addDays(3);

        Workout::factory()->create([
            'routine_id' => $routine->id,
            'workout_date' => $completedDate->toDateString(),
            'status' => WorkoutStatus::Completed,
        ]);
        Workout::factory()->create([
            'routine_id' => $routine->id,
            'workout_date' => $partialDate->toDateString(),
            'status' => WorkoutStatus::Partial,
        ]);
        Workout::factory()->create([
            'routine_id' => $routine->id,
            'workout_date' => $pendingDate->toDateString(),
            'status' => WorkoutStatus::Pending,
        ]);

        $response = $this->get(route('calendar', ['month' => $month->format('Y-m')]));

        $response->assertOk();
        $response->assertSee('Workout completed');
        $response->assertSee('Partially completed');
        $response->assertSee('No workout recorded');

        $html = $response->getContent();
        $this->assertMatchesRegularExpression('/data-date="'.$completedDate->toDateString().'"[^>]*data-marker="completed"/', $html);
        $this->assertMatchesRegularExpression('/data-date="'.$partialDate->toDateString().'"[^>]*data-marker="partial"/', $html);
        $this->assertMatchesRegularExpression('/data-date="'.$pendingDate->toDateString().'"[^>]*data-marker="none"/', $html);
        $this->assertMatchesRegularExpression('/data-date="'.$emptyDate->toDateString().'"[^>]*data-marker="none"/', $html);
    }

    public function test_a_day_with_mixed_records_is_partial(): void
    {
        $date = today()->startOfMonth()->toDateString();
        $first = Routine::factory()->create(['name' => 'Easy', 'is_current' => true]);
        $second = Routine::factory()->create(['name' => 'Extra', 'is_current' => false]);

        Workout::factory()->create([
            'routine_id' => $first->id,
            'workout_date' => $date,
            'status' => WorkoutStatus::Completed,
        ]);
        Workout::factory()->create([
            'routine_id' => $second->id,
            'workout_date' => $date,
            'status' => WorkoutStatus::Partial,
        ]);

        $html = $this->get(route('calendar', ['month' => today()->format('Y-m')]))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/data-date="'.$date.'"[^>]*data-marker="partial"/', $html);
    }

    public function test_day_page_shows_saved_exercises_not_later_edits(): void
    {
        $routine = Routine::factory()->create();
        $exercise = Exercise::factory()->create([
            'routine_id' => $routine->id,
            'name' => 'Knee push-ups',
            'type' => ExerciseType::Reps,
            'default_target' => 5,
        ]);
        $date = today()->startOfMonth()->toDateString();
        $workout = Workout::factory()->create([
            'routine_id' => $routine->id,
            'routine_name' => 'Morning Workout',
            'workout_date' => $date,
            'status' => WorkoutStatus::Completed,
            'rest_seconds' => 60,
            'rounds' => 2,
        ]);

        foreach ([1, 2] as $round) {
            WorkoutItem::factory()->create([
                'workout_id' => $workout->id,
                'exercise_id' => $exercise->id,
                'name' => 'Knee push-ups',
                'type' => ExerciseType::Reps,
                'target' => 3,
                'round_number' => $round,
                'sort_order' => 1,
                'completed' => true,
            ]);
            WorkoutItem::factory()->create([
                'workout_id' => $workout->id,
                'name' => 'Plank',
                'type' => ExerciseType::Timed,
                'target' => 15,
                'round_number' => $round,
                'sort_order' => 2,
                'completed' => false,
            ]);
        }

        $exercise->update([
            'name' => 'Handstand push-ups',
            'default_target' => 5,
        ]);

        $this->get(route('calendar.show', $date))
            ->assertOk()
            ->assertSee('Knee push-ups')
            ->assertSee('3 reps')
            ->assertSee('Plank')
            ->assertSee('15 sec')
            ->assertSee('Round 1')
            ->assertSee('Round 2')
            ->assertSee('Rest · 60 sec')
            ->assertSee('Workout complete')
            ->assertDontSee('Handstand push-ups')
            ->assertDontSee('5 reps');
    }

    public function test_invalid_calendar_dates_are_rejected(): void
    {
        $this->get('/calendar?month=2026-13')->assertNotFound();
        $this->get('/calendar/2026-02-31')->assertNotFound();
        $this->get('/calendar/nope')->assertNotFound();
    }

    public function test_empty_day_says_nothing_was_recorded(): void
    {
        $this->get(route('calendar.show', today()->toDateString()))
            ->assertOk()
            ->assertSee('No workout recorded');
    }
}
