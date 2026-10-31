<?php

namespace Tests\Feature\Api;

use App\Enums\ActivityType;
use App\Enums\TennisPartner;
use App\Enums\WorkoutStatus;
use App\Models\Activity;
use App\Models\Routine;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class McpApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['workout.mcp_token' => 'test-mcp-token']);
    }

    public function test_the_api_rejects_missing_or_wrong_tokens(): void
    {
        $this->getJson('/api/mcp/day')->assertUnauthorized();
        $this->withToken('wrong')->getJson('/api/mcp/day')->assertUnauthorized();
    }

    public function test_the_api_is_disabled_without_a_configured_token(): void
    {
        config(['workout.mcp_token' => '']);

        $this->withToken('anything')->getJson('/api/mcp/day')->assertStatus(503);
    }

    public function test_day_returns_workouts_activities_and_practices(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-14 09:00'));

        $routine = Routine::factory()->create();
        Workout::factory()->create([
            'routine_id' => $routine->id,
            'routine_name' => 'Morning Workout',
            'workout_date' => '2026-10-14',
            'status' => WorkoutStatus::Partial,
        ]);
        Activity::query()->create([
            'activity_date' => '2026-10-14',
            'type' => ActivityType::Tennis,
            'partner' => TennisPartner::Wal,
        ]);
        Activity::query()->create([
            'activity_date' => '2026-10-14',
            'type' => ActivityType::Stretching,
            'duration_minutes' => 10,
        ]);

        $this->withToken('test-mcp-token')
            ->getJson('/api/mcp/day')
            ->assertOk()
            ->assertJsonPath('date', '2026-10-14')
            ->assertJsonPath('calisthenics.0.status', 'partial')
            ->assertJsonPath('activities.0.type', 'tennis')
            ->assertJsonPath('practices.0.type', 'stretching');

        Carbon::setTestNow();
    }

    public function test_summary_counts_the_week_and_month(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-14 09:00'));

        Activity::query()->create([
            'activity_date' => '2026-10-13',
            'type' => ActivityType::Swimming,
            'duration_minutes' => 30,
        ]);
        Activity::query()->create([
            'activity_date' => '2026-10-12',
            'type' => ActivityType::Meditation,
            'duration_minutes' => 15,
        ]);

        $this->withToken('test-mcp-token')
            ->getJson('/api/mcp/summary?period=week')
            ->assertOk()
            ->assertJsonPath('period', 'week')
            ->assertJsonPath('total', 1)
            ->assertJsonPath('swimming', 1)
            ->assertJsonPath('meditation', 1)
            ->assertJsonPath('meditation_minutes', 15);

        $this->withToken('test-mcp-token')
            ->getJson('/api/mcp/summary?period=month&month=2026-10')
            ->assertOk()
            ->assertJsonPath('month', '2026-10')
            ->assertJsonPath('swim_minutes', 30);

        Carbon::setTestNow();
    }

    public function test_recent_lists_only_days_with_something_logged(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-14 09:00'));

        Activity::query()->create([
            'activity_date' => '2026-10-10',
            'type' => ActivityType::Swimming,
            'duration_minutes' => 20,
        ]);

        $this->withToken('test-mcp-token')
            ->getJson('/api/mcp/recent?days=14')
            ->assertOk()
            ->assertJsonPath('days.0.date', '2026-10-10')
            ->assertJsonCount(1, 'days');

        Carbon::setTestNow();
    }
}
