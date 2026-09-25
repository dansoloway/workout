<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($this->unlocked($request)) {
            return redirect()->route('today');
        }

        return view('enter');
    }

    public function store(Request $request): RedirectResponse
    {
        $password = (string) config('workout.password');
        $given = (string) $request->input('password', '');

        if ($password === '' || ! hash_equals($password, $given)) {
            return back()->withErrors(['password' => 'Wrong password.']);
        }

        $request->session()->regenerate();
        $request->session()->put('workout_unlocked', true);

        return redirect()->route('today');
    }

    private function unlocked(Request $request): bool
    {
        return (string) config('workout.password') === ''
            || $request->session()->get('workout_unlocked') === true;
    }
}
