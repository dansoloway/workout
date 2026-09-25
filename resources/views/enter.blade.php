@extends('layouts.app')

@section('title', 'Morning Workout')

@section('content')
    <h1 class="text-3xl font-semibold tracking-tight text-ink">Morning Workout</h1>
    <p class="mt-2 text-base text-muted">Enter the password to open today’s workout.</p>

    <form method="POST" action="{{ route('enter.store') }}" class="mt-6">
        @csrf
        <label for="password" class="sr-only">Password</label>
        <input
            id="password"
            name="password"
            type="password"
            autocomplete="current-password"
            autofocus
            required
            class="min-h-14 w-full rounded-2xl bg-card px-4 text-lg text-ink shadow-sm ring-1 ring-line outline-none focus:ring-2 focus:ring-pine"
        >
        @error('password')
            <p class="mt-2 text-base font-medium text-amber">{{ $message }}</p>
        @enderror
        <button type="submit" class="mt-3 min-h-14 w-full touch-manipulation rounded-full bg-pine text-lg font-semibold text-white shadow-sm shadow-pine/25">
            Continue
        </button>
    </form>

    @include('partials.install-app')
@endsection
