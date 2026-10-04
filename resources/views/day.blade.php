@extends('layouts.app')

@section('title', $isTodayPage ? 'Today' : $day->format('F j'))

@section('content')
    @php
        $card = 'rounded-3xl bg-card px-4 shadow-sm ring-1 ring-line';
    @endphp

    @unless ($isTodayPage)
        <p class="text-sm font-semibold uppercase tracking-wide text-muted">
            <a href="{{ route('calendar', ['month' => $day->format('Y-m')]) }}">Calendar</a>
        </p>
    @endunless

    <h1 @class(['text-3xl font-semibold tracking-tight text-ink', 'mt-2' => ! $isTodayPage])>
        {{ $isTodayPage ? 'Today' : $day->format('l, F j') }}
    </h1>
    @if ($isTodayPage)
        <p class="mt-1 text-base text-muted">{{ $day->format('l, F j') }}</p>
    @endif

    <section class="mt-6 space-y-2" aria-label="Recorded activities">
        @foreach ($workouts as $workout)
            @php
                $done = $workout->items->where('completed', true)->count();
            @endphp
            <a
                href="{{ route('calisthenics', $day->toDateString()) }}"
                class="{{ $card }} flex min-h-20 items-center gap-3"
                data-activity="calisthenics"
            >
                <span class="min-w-0 flex-1">
                    <span class="block text-xl font-semibold leading-snug text-ink">Calisthenics</span>
                    <span class="mt-0.5 block text-base text-muted">{{ $done }} of {{ $workout->items->count() }} · {{ $workout->status->label() }}</span>
                </span>
                <span class="text-3xl leading-none text-pine" aria-hidden="true">›</span>
            </a>
        @endforeach

        @foreach ($activities as $activity)
            <div class="{{ $card }} flex min-h-20 items-center gap-3" data-activity="{{ $activity->type->value }}">
                <span class="min-w-0 flex-1">
                    <span class="block text-xl font-semibold leading-snug text-ink">{{ $activity->type->label() }}</span>
                    <span class="mt-0.5 block text-base text-muted">{{ $activity->detail() }}</span>
                </span>
                <form method="POST" action="{{ route('activities.destroy', $activity) }}">
                    @csrf
                    @method('DELETE')
                    <button
                        type="submit"
                        class="min-h-12 touch-manipulation rounded-full px-4 text-base font-semibold text-muted ring-1 ring-line"
                        aria-label="Remove {{ $activity->type->label() }} {{ $activity->detail() }}"
                    >
                        Remove
                    </button>
                </form>
            </div>
        @endforeach

        @if ($workouts->isEmpty() && $activities->isEmpty())
            <p class="text-lg text-muted">No workout recorded</p>
        @endif
    </section>

    @if ($canLog)
        <section class="mt-8 space-y-2" aria-labelledby="add-heading">
            <h2 id="add-heading" class="mb-3 text-sm font-semibold tracking-wide text-pine">Add an activity</h2>

            @if ($workouts->isEmpty())
                @if ($hasRoutine)
                    <form method="POST" action="{{ route('calisthenics.start', $day->toDateString()) }}">
                        @csrf
                        <button type="submit" class="{{ $card }} flex min-h-20 w-full touch-manipulation items-center gap-3 text-left">
                            <span class="min-w-0 flex-1">
                                <span class="block text-xl font-semibold leading-snug text-ink">Calisthenics</span>
                                <span class="mt-0.5 block text-base text-muted">Start the checklist</span>
                            </span>
                            <span class="text-3xl leading-none text-pine" aria-hidden="true">›</span>
                        </button>
                    </form>
                @else
                    <p class="text-base text-muted">No workout routine is set up yet.</p>
                @endif
            @endif

            <div class="{{ $card }} py-4">
                <p class="text-xl font-semibold leading-snug text-ink">Tennis</p>
                <div class="mt-3 grid grid-cols-3 gap-2">
                    @foreach (\App\Enums\TennisPartner::cases() as $partner)
                        <form method="POST" action="{{ route('activities.store', $day->toDateString()) }}">
                            @csrf
                            <input type="hidden" name="type" value="tennis">
                            <input type="hidden" name="partner" value="{{ $partner->value }}">
                            <button
                                type="submit"
                                class="min-h-12 w-full touch-manipulation rounded-full bg-pine text-base font-semibold text-white shadow-sm shadow-pine/25"
                                aria-label="Add tennis with {{ $partner->label() }}"
                            >
                                {{ $partner->label() }}
                            </button>
                        </form>
                    @endforeach
                </div>
                @error('partner')
                    <p class="mt-2 text-base font-medium text-amber">{{ $message }}</p>
                @enderror
            </div>

            <form method="POST" action="{{ route('activities.store', $day->toDateString()) }}" class="{{ $card }} py-4">
                @csrf
                <input type="hidden" name="type" value="swimming">
                <label for="swim-minutes" class="block text-xl font-semibold leading-snug text-ink">Swimming</label>
                <div class="mt-3 flex gap-2">
                    <input
                        id="swim-minutes"
                        name="duration_minutes"
                        type="number"
                        inputmode="numeric"
                        min="1"
                        max="600"
                        required
                        placeholder="Minutes"
                        value="{{ old('duration_minutes') }}"
                        class="min-h-12 min-w-0 flex-1 rounded-2xl bg-paper px-4 text-lg text-ink ring-1 ring-line outline-none focus:ring-2 focus:ring-pine"
                    >
                    <button type="submit" class="min-h-12 touch-manipulation rounded-full bg-pine px-6 text-base font-semibold text-white shadow-sm shadow-pine/25">
                        Add
                    </button>
                </div>
                @error('duration_minutes')
                    <p class="mt-2 text-base font-medium text-amber">{{ $message }}</p>
                @enderror
            </form>
        </section>
    @endif

    @unless ($isTodayPage)
        @foreach ($workouts as $workout)
            <section class="mt-8">
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
    @endunless
@endsection
