<form
    method="POST"
    action="{{ route('workout-items.update', $item) }}"
    x-data="workoutItem({{ \Illuminate\Support\Js::from([
        'id' => $item->id,
        'name' => $item->name,
        'completed' => $item->completed,
        'type' => $item->type->value,
        'target' => $item->target,
        'url' => route('workout-items.update', $item),
    ]) }})"
    @submit.prevent="toggle()"
    class="h-full min-h-0"
    data-exercise="{{ $item->name }}"
    data-round="{{ $item->round_number }}"
>
    @csrf
    @method('PATCH')
    <input type="hidden" name="completed" value="{{ $item->completed ? '0' : '1' }}">

    <div
        class="flex h-full min-h-0 items-center gap-3 rounded-3xl px-3 shadow-sm"
        :class="completed ? 'bg-leaf-soft ring-1 ring-pine/15' : 'bg-card ring-1 ring-line'"
    >
        <button
            type="submit"
            class="flex min-w-0 flex-1 touch-manipulation items-center gap-3 text-left"
            :aria-pressed="completed.toString()"
        >
            <span
                class="flex size-12 shrink-0 items-center justify-center rounded-full border-2"
                :class="completed ? 'border-pine bg-pine text-white' : 'border-line bg-paper text-transparent'"
                aria-hidden="true"
            >
                <span class="text-2xl leading-none">✓</span>
            </span>
            <span class="min-w-0">
                <span class="block truncate text-xl font-semibold leading-snug text-ink">{{ $item->name }}</span>
                <span class="mt-0.5 block text-base text-muted">{{ $item->targetLabel() }}</span>
            </span>
        </button>

        <div x-show="type === 'timed'" x-cloak>
            @include('partials.timer-actions', ['seconds' => $item->target])
        </div>
    </div>
</form>
