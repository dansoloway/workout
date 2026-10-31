<section class="rounded-3xl bg-card px-4 py-4 shadow-sm ring-1 ring-line" data-summary="{{ $key }}">
    <div class="flex items-baseline justify-between gap-3">
        <h2 class="text-base font-semibold text-ink">{{ $title }}</h2>
        <p class="text-base text-muted"><span class="text-2xl font-semibold text-pine" data-total>{{ $summary['total'] }}</span> {{ $summary['total'] === 1 ? 'activity' : 'activities' }}</p>
    </div>
    <dl class="mt-3 grid grid-cols-3 gap-2 text-center">
        <div class="rounded-2xl bg-paper px-2 py-2">
            <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Calisthenics</dt>
            <dd class="mt-0.5 text-xl font-semibold text-ink" data-count="calisthenics">{{ $summary['calisthenics'] }}</dd>
        </div>
        <div class="rounded-2xl bg-paper px-2 py-2">
            <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Tennis</dt>
            <dd class="mt-0.5 text-xl font-semibold text-ink" data-count="tennis">{{ $summary['tennis'] }}</dd>
        </div>
        <div class="rounded-2xl bg-paper px-2 py-2">
            <dt class="text-xs font-semibold uppercase tracking-wide text-muted">Swimming</dt>
            <dd class="mt-0.5 text-xl font-semibold text-ink" data-count="swimming">{{ $summary['swimming'] }}</dd>
            @if ($summary['swim_minutes'] > 0)
                <dd class="text-xs text-muted">{{ $summary['swim_minutes'] }} min</dd>
            @endif
        </div>
    </dl>
    <dl class="mt-2 grid grid-cols-2 gap-2 text-center">
        <div class="rounded-2xl bg-stretch-soft px-2 py-2">
            <dt class="text-xs font-semibold uppercase tracking-wide text-stretch">Stretching</dt>
            <dd class="mt-0.5 text-xl font-semibold text-ink" data-practice="stretching">{{ $summary['stretching'] }}</dd>
            @if ($summary['stretch_minutes'] > 0)
                <dd class="text-xs text-muted">{{ $summary['stretch_minutes'] }} min</dd>
            @endif
        </div>
        <div class="rounded-2xl bg-calm-soft px-2 py-2">
            <dt class="text-xs font-semibold uppercase tracking-wide text-calm">Meditation</dt>
            <dd class="mt-0.5 text-xl font-semibold text-ink" data-practice="meditation">{{ $summary['meditation'] }}</dd>
            @if ($summary['meditation_minutes'] > 0)
                <dd class="text-xs text-muted">{{ $summary['meditation_minutes'] }} min</dd>
            @endif
        </div>
    </dl>
</section>
