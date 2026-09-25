<div
    x-data="restTimer({{ (int) $seconds }})"
    class="flex min-h-20 w-full items-center gap-3 rounded-3xl bg-card px-3 shadow-sm ring-1 ring-line"
>
    <div class="min-w-0 flex-1">
        <h2 class="truncate text-xl font-semibold leading-snug">Rest</h2>
        <p class="mt-0.5 text-base text-muted">{{ $seconds }} sec</p>
    </div>
    @include('partials.timer-actions', ['seconds' => $seconds])
</div>
