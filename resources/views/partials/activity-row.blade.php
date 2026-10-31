@php
    $dot = [
        'stretching' => 'bg-stretch',
        'meditation' => 'bg-calm',
    ][$activity->type->value] ?? null;
@endphp

<div class="{{ $card }} flex min-h-20 items-center gap-3" data-activity="{{ $activity->type->value }}">
    @if ($dot)
        <span class="size-3 shrink-0 rounded-full {{ $dot }}" aria-hidden="true"></span>
    @endif
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
