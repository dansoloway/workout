<?php

namespace Tests\Feature;

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
}
