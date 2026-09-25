@extends('layouts.app')

@section('title', $day->format('F j'))

@section('content')
    <p class="text-sm font-semibold uppercase tracking-wide text-muted">
        <a href="{{ route('calendar', ['month' => $day->format('Y-m')]) }}">Calendar</a>
    </p>
    <h1 class="mt-2 text-3xl font-semibold tracking-tight text-ink">{{ $day->format('l, F j') }}</h1>

    @if ($day->isToday())
        <a href="{{ route('today') }}" class="mt-5 flex min-h-14 items-center justify-center rounded-full bg-pine px-4 text-lg font-semibold text-white shadow-sm shadow-pine/25">
            Open today's workout
        </a>
    @endif

    @if ($workouts->isEmpty())
        <p class="mt-6 text-lg text-muted">No workout recorded</p>
    @else
        @foreach ($workouts as $workout)
            <section class="mt-6">
                <h2 class="text-xl font-semibold text-ink">{{ $workout->routine_name }}</h2>
                <p class="mt-1 text-base text-muted">{{ $workout->status->label() }}</p>

                <div class="mt-4 space-y-8">
                    @foreach ($workout->items->groupBy('round_number') as $round => $items)
                        <div class="space-y-3">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-muted">Round {{ $round }}</h3>
                            @foreach ($items as $item)
                                @include('partials.history-item', ['item' => $item])
                            @endforeach
                        </div>

                        @if (! $loop->last && $workout->rest_seconds > 0)
                            <p class="text-base text-muted">Rest · {{ $workout->rest_seconds }} sec</p>
                        @endif
                    @endforeach
                </div>
            </section>
        @endforeach
    @endif
@endsection
