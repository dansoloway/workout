<div @class([
    'flex items-center gap-4 rounded-3xl p-3 shadow-sm',
    'bg-leaf-soft ring-1 ring-pine/15' => $item->completed,
    'bg-card ring-1 ring-line' => ! $item->completed,
])>
    <span @class([
        'flex size-12 shrink-0 items-center justify-center rounded-full border-2 text-2xl leading-none',
        'border-pine bg-pine text-white' => $item->completed,
        'border-line bg-paper text-transparent' => ! $item->completed,
    ]) aria-hidden="true">✓</span>
    <span class="min-w-0">
        <span class="block text-xl font-semibold leading-snug">{{ $item->name }}</span>
        <span class="mt-0.5 block text-base text-muted">{{ $item->targetLabel() }}</span>
        <span class="sr-only">{{ $item->completed ? 'Completed' : 'Not completed' }}</span>
    </span>
</div>
