@extends('layouts.app')

@section('title', $workout->routine_name)

@section('full')
    @php
        $done = $workout->items->where('completed', true)->count();
        $total = $workout->items->count();
        $groups = $workout->items->groupBy('round_number');
        $screens = [];

        foreach ($groups as $round => $items) {
            $screens[] = [
                'kind' => 'round',
                'round' => $round,
                'items' => $items,
                'label' => 'Round '.$round,
            ];

            if ((string) $round !== (string) $groups->keys()->last() && $workout->rest_seconds > 0) {
                $screens[] = [
                    'kind' => 'rest',
                    'label' => 'Rest',
                ];
            }
        }

        $initial = max(count($screens) - 1, 0);

        foreach ($screens as $index => $screen) {
            if ($screen['kind'] === 'round' && $screen['items']->contains(fn ($item) => ! $item->completed)) {
                $initial = $index;
                break;
            }
        }
    @endphp

    <div
        x-data="todayScreen({{ \Illuminate\Support\Js::from([
            'status' => $workout->status->value,
            'done' => $done,
            'total' => $total,
            'step' => $initial,
            'labels' => array_column($screens, 'label'),
        ]) }})"
        data-workout-status="{{ $workout->status->value }}"
        :data-workout-status="status"
        @item-changed.window="applyLocal($event)"
        @workout-status.window="applyServer($event)"
        class="mx-auto flex h-dvh max-w-md flex-col overflow-hidden px-4 pt-5"
        style="padding-bottom: calc(6.25rem + env(safe-area-inset-bottom))"
    >
        <header class="shrink-0">
            <h1 class="text-3xl font-semibold tracking-tight text-ink">{{ $workout->routine_name }}</h1>
            <p class="mt-1 text-base text-muted">
                {{ $workout->workout_date->format('l, F j') }}
                · <span x-text="done">{{ $done }}</span> of <span x-text="total">{{ $total }}</span> done
            </p>
            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-white/80 ring-1 ring-line" aria-hidden="true">
                <div
                    class="h-full rounded-full bg-pine"
                    :style="`width: ${total ? Math.round((done / total) * 100) : 0}%`"
                    style="width: {{ $total > 0 ? (int) round(($done / $total) * 100) : 0 }}%"
                ></div>
            </div>
        </header>

        <p x-show="pendingSync > 0" x-cloak class="mt-3 shrink-0 text-sm font-medium text-amber">
            Saved on this phone. Syncs when online.
        </p>

        <div class="mt-4 min-h-0 flex-1">
            @foreach ($screens as $index => $screen)
                <section
                    x-show="step === {{ $index }}"
                    @if ($index !== $initial) x-cloak @endif
                    class="flex h-full min-h-0 flex-col"
                    data-screen="{{ $screen['label'] }}"
                >
                    @if ($screen['kind'] === 'round')
                        <h2 class="mb-3 shrink-0 text-sm font-semibold tracking-wide text-pine">{{ $screen['label'] }}</h2>
                        <div
                            class="grid min-h-0 flex-1 gap-2"
                            style="grid-template-rows: repeat({{ max($screen['items']->count(), 1) }}, minmax(0, 1fr))"
                        >
                            @foreach ($screen['items'] as $item)
                                @include('partials.workout-item', ['item' => $item])
                            @endforeach
                        </div>
                    @else
                        <div class="flex h-full items-center">
                            @include('partials.rest-timer', ['seconds' => $workout->rest_seconds])
                        </div>
                    @endif
                </section>
            @endforeach
        </div>

        <div class="shrink-0 pt-3">
            <p
                x-show="status === 'completed'"
                x-cloak
                class="mb-2 rounded-full bg-pine px-4 py-3 text-center text-lg font-semibold text-white shadow-sm shadow-pine/20"
            >
                Workout complete ✓
            </p>
            @if (count($screens) > 1)
                <div class="flex gap-2">
                    <button
                        type="button"
                        x-show="step > 0"
                        x-cloak
                        @click="back()"
                        class="min-h-14 flex-1 touch-manipulation rounded-full bg-card text-lg font-semibold text-ink shadow-sm ring-1 ring-line"
                    >
                        Back
                    </button>
                    <button
                        type="button"
                        x-show="step < labels.length - 1"
                        @if ($initial >= count($screens) - 1) x-cloak @endif
                        @click="next()"
                        class="min-h-14 flex-1 touch-manipulation rounded-full bg-pine text-lg font-semibold text-white shadow-sm shadow-pine/25"
                    >
                        <span x-text="labels[step + 1] ?? 'Next'">{{ $screens[$initial + 1]['label'] ?? 'Next' }}</span>
                    </button>
                </div>
            @endif
        </div>
    </div>
@endsection
