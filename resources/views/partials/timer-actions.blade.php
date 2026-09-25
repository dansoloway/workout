@php
    $clock = intdiv($seconds, 60).':'.str_pad((string) ($seconds % 60), 2, '0', STR_PAD_LEFT);
@endphp

<div class="flex shrink-0 items-center gap-2">
    <div class="flex w-14 flex-col items-end justify-center leading-none">
        <p class="font-mono text-xl font-semibold tabular-nums tracking-tight text-pine" x-text="display">{{ $clock }}</p>
        <button type="button" x-show="showResume" x-cloak @click="reset()" class="mt-1 text-sm font-semibold text-muted">Reset</button>
    </div>
    <button type="button" @click="primary()" class="flex h-12 min-w-[4.5rem] touch-manipulation items-center justify-center rounded-full bg-pine px-4 text-base font-semibold text-white shadow-sm shadow-pine/25">
        <span x-text="primaryLabel">Start</span>
    </button>
</div>
