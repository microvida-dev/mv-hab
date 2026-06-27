@props([
    'steps' => [],
])

@php
    $classes = [
        'done' => 'bg-emerald-50 text-emerald-800 border-emerald-100',
        'current' => 'bg-civic-50 text-civic-800 border-civic-100',
        'warning' => 'bg-amber-50 text-amber-800 border-amber-100',
        'blocked' => 'bg-red-50 text-red-800 border-red-100',
        'pending' => 'bg-white text-ink-500 border-ink-100',
        'skipped' => 'bg-ink-50 text-ink-400 border-ink-100',
    ];
@endphp

<section class="rounded-md border border-ink-100 bg-white p-5">
    <h2 class="text-base font-semibold text-ink-900">Progresso visual</h2>
    <div class="mt-4 grid gap-2 md:grid-cols-2 xl:grid-cols-5">
        @foreach ($steps as $step)
            <div class="rounded-md border px-3 py-2 text-sm font-semibold {{ $classes[$step['status']] ?? $classes['pending'] }}">
                {{ $step['label'] }}
            </div>
        @endforeach
    </div>
</section>
