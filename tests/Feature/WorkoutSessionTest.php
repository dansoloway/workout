<?php

namespace Tests\Feature;

use App\Enums\ExerciseType;
use App\Enums\WorkoutStatus;
use App\Models\Exercise;
use App\Models\Routine;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutItem;
use App\Services\WorkoutGenerator;
use Database\Seeders\MorningWorkoutSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WorkoutSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_opens_today(): void
    {
        $this->get('/')->assertRedirect('/today');
    }

    public function test_today_without_a_routine_is_a_quiet_empty_state(): void
    {
        $this->get('/today')
            ->assertOk()
            ->assertSee('No workout routine is set up yet.');
    }

    public function test_today_creates_the_seeded_workout_once(): void
    {
        $this->seed(MorningWorkoutSeeder::class);

        $this->get('/today')
            ->assertOk()
            ->assertSee('Morning Workout')
            ->assertSee('Cat–cow')
            ->assertSee('Bodyweight squats')
            ->assertSee('Knee push-ups')
            ->assertSee('Plank')
            ->assertSee('Sit-ups')
            ->assertSee('8 reps')
            ->assertSee('3 reps')
            ->assertSee('15 sec')
            ->assertSee('5 reps')
            ->assertSee('Round 1')
            ->assertSee('Round 2')
            ->assertSee('Rest')
            ->assertSee('60 sec')
            ->assertSee('data-workout-status="pending"', false);

        $this->get('/today')->assertOk();

        $this->assertSame(1, Workout::query()->count());
        $this->assertSame(10, WorkoutItem::query()->count());
        $this->assertSame(WorkoutStatus::Pending, Workout::query()->first()->status);
    }

    public function test_checking_one_item_saves_immediately(): void
    {
        $workout = $this->startToday();
        $item = $workout->items->first();

        $this->patchJson(route('workout-items.update', $item), ['completed' => true])
            ->assertOk()
            ->assertJsonPath('item.completed', true)
            ->assertJsonPath('workout.status', 'partial')
            ->assertJsonPath('workout.done', 1)
            ->assertJsonPath('workout.total', 10);

        $this->assertTrue($item->refresh()->completed);
        $this->assertNotNull($item->completed_at);
        $workout->refresh();
        $this->assertSame(WorkoutStatus::Partial, $workout->status);
        $this->assertNotNull($workout->started_at);
        $this->assertNull($workout->completed_at);
    }

    public function test_checking_works_without_javascript(): void
    {
        $workout = $this->startToday();
        $item = $workout->items->first();

        $this->from('/today')
            ->patch(route('workout-items.update', $item), ['completed' => '1'])
            ->assertRedirect('/today');

        $this->assertTrue($item->refresh()->completed);
        $this->assertSame(WorkoutStatus::Partial, $workout->refresh()->status);
    }

    public function test_completing_every_item_marks_the_workout_complete(): void
    {
        $workout = $this->startToday();

        foreach ($workout->items as $item) {
            $this->patchJson(route('workout-items.update', $item), ['completed' => true])
                ->assertOk();
        }

        $workout->refresh();
        $this->assertSame(WorkoutStatus::Completed, $workout->status);
        $this->assertNotNull($workout->completed_at);
        $this->assertNotNull($workout->started_at);
        $this->assertSame(10, $workout->items()->where('completed', true)->count());

        $this->get('/today')->assertSee('data-workout-status="completed"', false);
    }

    public function test_unchecking_reopens_a_finished_workout(): void
    {
        $workout = $this->startToday();

        foreach ($workout->items as $item) {
            $this->patchJson(route('workout-items.update', $item), ['completed' => true])->assertOk();
        }

        $first = $workout->items->first();

        $this->patchJson(route('workout-items.update', $first), ['completed' => false])
            ->assertOk()
            ->assertJsonPath('workout.status', 'partial')
            ->assertJsonPath('item.completed', false);

        $workout->refresh();
        $this->assertSame(WorkoutStatus::Partial, $workout->status);
        $this->assertNull($workout->completed_at);
        $this->assertNotNull($workout->started_at);
        $this->assertNull($first->refresh()->completed_at);

        foreach ($workout->items as $item) {
            $this->patchJson(route('workout-items.update', $item), ['completed' => false])->assertOk();
        }

        $workout->refresh();
        $this->assertSame(WorkoutStatus::Pending, $workout->status);
        $this->assertNull($workout->started_at);
        $this->assertNull($workout->completed_at);
    }

    public function test_completion_must_be_boolean(): void
    {
        $workout = $this->startToday();
        $item = $workout->items->first();

        $this->patchJson(route('workout-items.update', $item), [])
            ->assertUnprocessable();

        $this->patchJson(route('workout-items.update', $item), ['completed' => 'yes'])
            ->assertUnprocessable();

        $this->assertFalse($item->refresh()->completed);
    }

    public function test_historical_snapshots_stay_fixed_when_the_routine_changes(): void
    {
        $this->seed(MorningWorkoutSeeder::class);
        $generator = app(WorkoutGenerator::class);
        $today = $generator->todaysWorkout();

        Exercise::query()->where('name', 'Knee push-ups')->firstOrFail()->update([
            'name' => 'Full push-ups',
            'default_target' => 5,
        ]);

        Routine::query()->current()->firstOrFail()->update([
            'name' => 'Updated Workout',
            'rounds' => 4,
            'rest_seconds' => 30,
        ]);

        $again = $generator->todaysWorkout();

        $this->assertSame($today->id, $again->id);
        $this->assertSame('Morning Workout', $again->routine_name);
        $this->assertSame(2, $again->rounds);
        $this->assertSame(60, $again->rest_seconds);
        $this->assertCount(10, $again->items);
        $this->assertSame(2, $again->items->where('name', 'Knee push-ups')->where('target', 3)->count());
        $this->assertFalse($again->items->contains('name', 'Full push-ups'));

        $tomorrow = $generator->forDate(today()->addDay());

        $this->assertSame('Updated Workout', $tomorrow->routine_name);
        $this->assertSame(4, $tomorrow->rounds);
        $this->assertSame(30, $tomorrow->rest_seconds);
        $this->assertCount(20, $tomorrow->items);
        $this->assertSame(4, $tomorrow->items->where('name', 'Full push-ups')->where('target', 5)->count());
        $this->assertSame(2, $today->fresh()->items()->where('name', 'Knee push-ups')->where('target', 3)->count());
    }

    public function test_generator_follows_routine_size_and_skips_inactive_exercises(): void
    {
        $routine = Routine::factory()->create([
            'name' => 'Short routine',
            'rounds' => 3,
            'rest_seconds' => 10,
            'is_current' => true,
        ]);

        Exercise::factory()->create([
            'routine_id' => $routine->id,
            'name' => 'Breathing',
            'type' => ExerciseType::Timed,
            'default_target' => 20,
            'sort_order' => 1,
        ]);

        Exercise::factory()->create([
            'routine_id' => $routine->id,
            'name' => 'Hidden',
            'default_target' => 99,
            'sort_order' => 2,
            'active' => false,
        ]);

        $workout = app(WorkoutGenerator::class)->todaysWorkout();

        $this->assertSame('Short routine', $workout->routine_name);
        $this->assertSame(3, $workout->rounds);
        $this->assertSame(10, $workout->rest_seconds);
        $this->assertCount(3, $workout->items);
        $this->assertEquals([1, 2, 3], $workout->items->pluck('round_number')->all());
        $this->assertTrue($workout->items->every(
            fn (WorkoutItem $item) => $item->name === 'Breathing' && $item->target === 20 && $item->type === ExerciseType::Timed,
        ));
        $this->assertFalse($workout->items->contains(fn (WorkoutItem $item) => $item->name === 'Hidden'));
    }

    public function test_today_uses_the_current_workout_routine_only(): void
    {
        $this->seed(MorningWorkoutSeeder::class);

        DB::table('routines')->insert([
            'name' => 'Evening Meditation',
            'kind' => 'meditation',
            'rounds' => 1,
            'rest_seconds' => 0,
            'is_current' => true,
            'active' => true,
            'owner_scope' => 0,
            'user_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get('/today')
            ->assertOk()
            ->assertSee('Morning Workout')
            ->assertSee('Cat–cow')
            ->assertDontSee('Evening Meditation');
    }

    public function test_another_persons_workout_is_hidden(): void
    {
        $this->seed(MorningWorkoutSeeder::class);

        $user = User::factory()->create();
        $routine = Routine::factory()->create([
            'user_id' => $user->id,
            'name' => 'Secret Routine',
            'is_current' => true,
        ]);
        $workout = Workout::factory()->create([
            'user_id' => $user->id,
            'routine_id' => $routine->id,
            'routine_name' => 'Secret Routine',
            'workout_date' => today()->toDateString(),
            'status' => WorkoutStatus::Completed,
        ]);
        $item = WorkoutItem::factory()->create([
            'workout_id' => $workout->id,
            'name' => 'Secret move',
        ]);

        $this->get('/today')->assertOk()->assertDontSee('Secret');
        $this->get(route('calendar.show', today()->toDateString()))->assertDontSee('Secret');
        $this->patchJson(route('workout-items.update', $item), ['completed' => true])->assertNotFound();
    }

    public function test_manifest_is_installable(): void
    {
        $this->get('/manifest.webmanifest')
            ->assertOk()
            ->assertHeader('content-type', 'application/manifest+json')
            ->assertJsonPath('display', 'standalone')
            ->assertJsonPath('start_url', '/today')
            ->assertJsonPath('background_color', '#eef3fb')
            ->assertJsonPath('scope', '/');
    }

    private function startToday(): Workout
    {
        $this->seed(MorningWorkoutSeeder::class);
        $this->get('/today')->assertOk();

        return Workout::query()->with('items')->firstOrFail();
    }
}
