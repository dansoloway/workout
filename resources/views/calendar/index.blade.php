@extends('layouts.app')

@section('title', $month->format('F Y'))

@section('content')
    @php
        $markerLabel = [
            'completed' => 'workout completed',
            'partial' => 'partially completed',
            'none' => 'no workout recorded',
        ];
        $markerClass = [
            'completed' => 'bg-leaf',
            'partial' => 'bg-amber',
            'none' => 'bg-transparent',
        ];
    @endphp

    <div class="mb-5 flex items-center justify-between">
        <a href="{{ route('calendar', ['month' => $previous]) }}" class="flex size-12 items-center justify-center rounded-full bg-card text-2xl leading-none text-pine shadow-sm ring-1 ring-line" aria-label="Previous month">‹</a>
        <h1 class="text-2xl font-semibold tracking-tight text-ink">{{ $month->format('F Y') }}</h1>
        <a href="{{ route('calendar', ['month' => $next]) }}" class="flex size-12 items-center justify-center rounded-full bg-card text-2xl leading-none text-pine shadow-sm ring-1 ring-line" aria-label="Next month">›</a>
    </div>

    <div class="grid grid-cols-7 gap-1 text-center text-sm font-semibold text-muted">
        @foreach (['S', 'M', 'T', 'W', 'T', 'F', 'S'] as $weekday)
            <div class="py-2">{{ $weekday }}</div>
        @endforeach
    </div>

    <div class="grid grid-cols-7 gap-1">
        @foreach ($days as $day)
            <a
                href="{{ route('calendar.show', $day['date']->toDateString()) }}"
                data-date="{{ $day['date']->toDateString() }}"
                data-marker="{{ $day['marker'] }}"
                aria-label="{{ $day['date']->format('F j') }}, {{ $markerLabel[$day['marker']] }}"
                @class([
                    'flex aspect-square flex-col items-center justify-center rounded-2xl text-base',
                    'text-ink' => $day['in_month'] && ! $day['is_today'],
                    'text-muted/40' => ! $day['in_month'],
                    'bg-leaf-soft font-semibold text-pine ring-2 ring-pine' => $day['is_today'],
                ])
            >
                <span>{{ $day['date']->day }}</span>
                <span class="mt-1 size-2 rounded-full {{ $markerClass[$day['marker']] }}"></span>
            </a>
        @endforeach
    </div>

    <ul class="mt-6 space-y-2 text-base text-muted">
        <li class="flex items-center gap-3"><span class="size-2.5 rounded-full bg-leaf"></span> Workout completed</li>
        <li class="flex items-center gap-3"><span class="size-2.5 rounded-full bg-amber"></span> Partially completed</li>
        <li class="flex items-center gap-3"><span class="size-2.5 rounded-full border border-muted"></span> No workout recorded</li>
    </ul>
@endsection
