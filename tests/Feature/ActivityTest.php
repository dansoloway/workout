<?php

namespace Tests\Feature;

use App\Enums\ActivityType;
use App\Enums\TennisPartner;
use App\Enums\WorkoutStatus;
use App\Models\Activity;
use App\Models\Routine;
use App\Models\Workout;
use Database\Seeders\MorningWorkoutSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_day_holds_calisthenics_two_tennis_sessions_and_a_swim(): void
    {
        $this->seed(MorningWorkoutSeeder::class);
        $today = today()->toDateString();

        $this->post(route('calisthenics.start', $today))->assertRedirect();
        $this->post(route('activities.store', $today), ['type' => 'tennis', 'partner' => 'osnat'])->assertRedirect();
        $this->post(route('activities.store', $today), ['type' => 'tennis', 'partner' => 'wal'])->assertRedirect();
        $this->post(route('activities.store', $today), ['type' => 'swimming', 'duration_minutes' => 30])->assertRedirect();

        $this->assertSame(1, Workout::query()->count());
        $this->assertSame(2, Activity::query()->where('type', ActivityType::Tennis)->count());

        $swim = Activity::query()->where('type', ActivityType::Swimming)->sole();
        $this->assertSame(30, $swim->duration_minutes);
        $this->assertNull($swim->partner);

        $this->get('/today')
            ->assertOk()
            ->assertSee('Calisthenics')
            ->assertSee('Osnat')
            ->assertSee('Wal')
            ->assertSee('30 min')
            ->assertDontSee('No workout recorded');
    }

    public function test_tennis_needs_a_listed_partner_and_swimming_needs_minutes(): void
    {
        $today = today()->toDateString();

        $this->post(route('activities.store', $today), ['type' => 'tennis'])->assertSessionHasErrors('partner');
        $this->post(route('activities.store', $today), ['type' => 'tennis', 'partner' => 'roger'])->assertSessionHasErrors('partner');
        $this->post(route('activities.store', $today), ['type' => 'swimming'])->assertSessionHasErrors('duration_minutes');
        $this->post(route('activities.store', $today), ['type' => 'swimming', 'duration_minutes' => 0])->assertSessionHasErrors('duration_minutes');
        $this->post(route('activities.store', $today), ['type' => 'yoga'])->assertSessionHasErrors('type');

        $this->assertSame(0, Activity::query()->count());
    }

    public function test_tennis_ignores_minutes_and_swimming_ignores_partner(): void
    {
        $today = today()->toDateString();

        $this->post(route('activities.store', $today), ['type' => 'tennis', 'partner' => 'shalev', 'duration_minutes' => 40]);
        $this->post(route('activities.store', $today), ['type' => 'swimming', 'duration_minutes' => 20, 'partner' => 'osnat']);

        $tennis = Activity::query()->where('type', ActivityType::Tennis)->sole();
        $this->assertSame(TennisPartner::Shalev, $tennis->partner);
        $this->assertNull($tennis->duration_minutes);
        $this->assertNull(Activity::query()->where('type', ActivityType::Swimming)->sole()->partner);
    }

    public function test_a_past_day_can_be_filled_in_but_not_a_future_one(): void
    {
        $yesterday = today()->subDay()->toDateString();
        $tomorrow = today()->addDay()->toDateString();

        $this->post(route('activities.store', $yesterday), ['type' => 'swimming', 'duration_minutes' => 25])
            ->assertRedirect();
        $this->post(route('activities.store', $tomorrow), ['type' => 'swimming', 'duration_minutes' => 25])
            ->assertNotFound();

        $this->get(route('calendar.show', $yesterday))->assertOk()->assertSee('25 min');
        $this->get(route('calendar.show', $tomorrow))->assertOk()->assertDontSee('Add an activity');
    }

    public function test_an_activity_can_be_removed(): void
    {
        $activity = Activity::query()->create([
            'activity_date' => today()->toDateString(),
            'type' => ActivityType::Tennis,
            'partner' => TennisPartner::Osnat,
        ]);

        $this->delete(route('activities.destroy', $activity))->assertRedirect();

        $this->assertSame(0, Activity::query()->count());
    }

    public function test_a_logged_activity_marks_the_calendar_day(): void
    {
        $date = today()->startOfMonth()->toDateString();

        Activity::query()->create([
            'activity_date' => $date,
            'type' => ActivityType::Swimming,
            'duration_minutes' => 15,
        ]);

        $html = $this->get(route('calendar', ['month' => today()->format('Y-m')]))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/data-date="'.$date.'"[^>]*data-marker="completed"/', $html);
    }

    public function test_week_and_month_totals_count_each_activity_type(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-14 09:00'));

        $routine = Routine::factory()->create();

        Workout::factory()->create([
            'routine_id' => $routine->id,
            'workout_date' => '2026-10-11',
            'status' => WorkoutStatus::Completed,
        ]);
        Workout::factory()->create([
            'routine_id' => $routine->id,
            'workout_date' => '2026-10-12',
            'status' => WorkoutStatus::Pending,
        ]);
        Workout::factory()->create([
            'routine_id' => $routine->id,
            'workout_date' => '2026-10-02',
            'status' => WorkoutStatus::Partial,
        ]);

        foreach (['2026-10-11', '2026-10-17', '2026-10-03'] as $date) {
            Activity::query()->create(['activity_date' => $date, 'type' => ActivityType::Tennis, 'partner' => TennisPartner::Wal]);
        }

        Activity::query()->create(['activity_date' => '2026-10-13', 'type' => ActivityType::Swimming, 'duration_minutes' => 30]);
        Activity::query()->create(['activity_date' => '2026-10-10', 'type' => ActivityType::Swimming, 'duration_minutes' => 20]);
        Activity::query()->create(['activity_date' => '2026-09-30', 'type' => ActivityType::Swimming, 'duration_minutes' => 99]);

        $html = $this->get(route('calendar', ['month' => '2026-10']))->assertOk()->getContent();

        $this->assertSame(
            ['total' => '4', 'calisthenics' => '1', 'tennis' => '2', 'swimming' => '1'],
            $this->summaryCounts($html, 'week'),
        );
        $this->assertSame(
            ['total' => '7', 'calisthenics' => '2', 'tennis' => '3', 'swimming' => '2'],
            $this->summaryCounts($html, 'month'),
        );
        $this->assertStringContainsString('50 min', $html);
        $this->assertMatchesRegularExpression('/data-date="2026-09-27"/', $html);

        Carbon::setTestNow();
    }

    public function test_stretching_and_meditation_are_logged_apart_from_workouts(): void
    {
        $today = today()->toDateString();

        $this->post(route('activities.store', $today), ['type' => 'stretching', 'duration_minutes' => 10])->assertRedirect();
        $this->post(route('activities.store', $today), ['type' => 'meditation', 'duration_minutes' => 15])->assertRedirect();

        $this->assertSame(10, Activity::query()->where('type', ActivityType::Stretching)->sole()->duration_minutes);
        $this->assertSame(15, Activity::query()->where('type', ActivityType::Meditation)->sole()->duration_minutes);

        $this->get('/today')
            ->assertOk()
            ->assertSee('No workout recorded')
            ->assertSeeInOrder(['Stretching &amp; meditation', 'Stretching', '10 min', 'Meditation', '15 min'], false);
    }

    public function test_stretching_and_meditation_need_minutes(): void
    {
        $today = today()->toDateString();

        $this->post(route('activities.store', $today), ['type' => 'stretching'])
            ->assertSessionHasErrors(['duration_minutes' => 'Enter how many minutes you stretched.']);
        $this->post(route('activities.store', $today), ['type' => 'meditation', 'duration_minutes' => 0])
            ->assertSessionHasErrors('duration_minutes');

        $this->assertSame(0, Activity::query()->count());
    }

    public function test_stretching_and_meditation_get_their_own_calendar_dots_and_counts(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-14 09:00'));

        Activity::query()->create(['activity_date' => '2026-10-12', 'type' => ActivityType::Stretching, 'duration_minutes' => 10]);
        Activity::query()->create(['activity_date' => '2026-10-12', 'type' => ActivityType::Meditation, 'duration_minutes' => 20]);
        Activity::query()->create(['activity_date' => '2026-10-13', 'type' => ActivityType::Meditation, 'duration_minutes' => 5]);
        Activity::query()->create(['activity_date' => '2026-10-05', 'type' => ActivityType::Stretching, 'duration_minutes' => 15]);

        $html = $this->get(route('calendar', ['month' => '2026-10']))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/data-date="2026-10-12"[^>]*data-marker="none"[^>]*data-practices="stretching meditation"/', $html);
        $this->assertMatchesRegularExpression('/data-date="2026-10-13"[^>]*data-practices="meditation"/', $html);
        $this->assertMatchesRegularExpression('/data-date="2026-10-14"[^>]*data-practices=""/', $html);

        $this->assertSame('0', $this->summaryCounts($html, 'week')['total']);
        $this->assertSame(['stretching' => '1', 'meditation' => '2'], $this->practiceCounts($html, 'week'));
        $this->assertSame(['stretching' => '2', 'meditation' => '2'], $this->practiceCounts($html, 'month'));
        $this->assertStringContainsString('25 min', $html);

        Carbon::setTestNow();
    }

    public function test_another_month_shows_only_the_month_total(): void
    {
        $html = $this->get(route('calendar', ['month' => today()->subMonths(2)->format('Y-m')]))->assertOk()->getContent();

        $this->assertStringNotContainsString('data-summary="week"', $html);
        $this->assertStringContainsString('data-summary="month"', $html);
    }

    /**
     * @return array<string, string>
     */
    private function summaryCounts(string $html, string $key): array
    {
        preg_match('/data-summary="'.$key.'"(.*?)<\/section>/s', $html, $section);
        $block = $section[1] ?? '';

        preg_match('/data-total>(\d+)</', $block, $total);
        preg_match_all('/data-count="(\w+)">(\d+)</', $block, $counts);

        return ['total' => $total[1] ?? ''] + array_combine($counts[1], $counts[2]);
    }

    /**
     * @return array<string, string>
     */
    private function practiceCounts(string $html, string $key): array
    {
        preg_match('/data-summary="'.$key.'"(.*?)<\/section>/s', $html, $section);
        preg_match_all('/data-practice="(\w+)">(\d+)</', $section[1] ?? '', $counts);

        return array_combine($counts[1], $counts[2]);
    }
}
