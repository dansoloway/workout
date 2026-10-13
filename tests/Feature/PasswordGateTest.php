<?php

namespace Tests\Feature;

use App\Support\PasswordGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['workout.password' => 'gamal123']);
    }

    public function test_the_workout_stays_behind_the_password(): void
    {
        $this->get('/today')->assertRedirect(route('enter'));
        $this->get('/')->assertRedirect(route('enter'));
        $this->get('/enter')->assertOk()->assertSee('Password');
    }

    public function test_the_right_password_opens_today(): void
    {
        $this->post('/enter', ['password' => 'nope'])->assertSessionHasErrors('password');

        $this->post('/enter', ['password' => 'gamal123'])
            ->assertRedirect(route('today'))
            ->assertSessionHas('workout_unlocked', true);
    }

    public function test_remember_me_keeps_the_phone_unlocked_for_a_month(): void
    {
        $response = $this->post('/enter', ['password' => 'gamal123', 'remember' => '1'])
            ->assertCookie(PasswordGate::COOKIE);

        $cookie = $response->getCookie(PasswordGate::COOKIE);
        $this->assertEqualsWithDelta(now()->addMinutes(60 * 24 * 30)->timestamp, $cookie->getExpiresTime(), 5);

        $this->flushSession();

        $this->withCookie(PasswordGate::COOKIE, $cookie->getValue())
            ->get('/today')
            ->assertOk();
    }

    public function test_without_remember_me_no_cookie_is_set(): void
    {
        $this->post('/enter', ['password' => 'gamal123'])
            ->assertCookieMissing(PasswordGate::COOKIE);
    }

    public function test_changing_the_password_forgets_remembered_phones(): void
    {
        $cookie = $this->post('/enter', ['password' => 'gamal123', 'remember' => '1'])
            ->getCookie(PasswordGate::COOKIE);

        $this->flushSession();
        config(['workout.password' => 'something-new']);

        $this->withCookie(PasswordGate::COOKIE, $cookie->getValue())
            ->get('/today')
            ->assertRedirect(route('enter'));
    }
}
