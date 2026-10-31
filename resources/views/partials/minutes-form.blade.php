@php
    $id = $type->value.'-minutes';
    $isOld = old('type') === $type->value;
    $button = [
        'stretching' => 'bg-stretch shadow-stretch/25',
        'meditation' => 'bg-calm shadow-calm/25',
    ][$type->value] ?? 'bg-pine shadow-pine/25';
@endphp

<form method="POST" action="{{ route('activities.store', $day->toDateString()) }}" class="{{ $card }} py-4" data-add="{{ $type->value }}">
    @csrf
    <input type="hidden" name="type" value="{{ $type->value }}">
    <label for="{{ $id }}" class="block text-xl font-semibold leading-snug text-ink">{{ $type->label() }}</label>
    <div class="mt-3 flex gap-2">
        <input
            id="{{ $id }}"
            name="duration_minutes"
            type="number"
            inputmode="numeric"
            min="1"
            max="600"
            required
            placeholder="Minutes"
            value="{{ $isOld ? old('duration_minutes') : '' }}"
            class="min-h-12 min-w-0 flex-1 rounded-2xl bg-paper px-4 text-lg text-ink ring-1 ring-line outline-none focus:ring-2 focus:ring-pine"
        >
        <button type="submit" class="min-h-12 touch-manipulation rounded-full px-6 text-base font-semibold text-white shadow-sm {{ $button }}">
            Add
        </button>
    </div>
    @if ($isOld)
        @error('duration_minutes')
            <p class="mt-2 text-base font-medium text-amber">{{ $message }}</p>
        @enderror
    @endif
</form>
