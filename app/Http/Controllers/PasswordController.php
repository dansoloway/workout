<?php

namespace App\Http\Controllers;

use App\Support\PasswordGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (PasswordGate::unlocked($request)) {
            return redirect()->route('today');
        }

        return view('enter');
    }

    public function store(Request $request): RedirectResponse
    {
        if (! PasswordGate::matches((string) $request->input('password', ''))) {
            return back()->withErrors(['password' => 'Wrong password.']);
        }

        PasswordGate::unlock($request, $request->boolean('remember'));

        return redirect()->route('today');
    }
}
