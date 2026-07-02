@props([
    'summary' => [],
    'agenda' => [],
    'filters' => [],
])

@php
    $view = $filters['view'] ?? 'day';
    $date = \Illuminate\Support\Carbon::parse($filters['date'] ?? now()->toDateString());

    $previousDate = match ($view) {
        'week' => $date->copy()->subWeek(),
        'month' => $date->copy()->subMonth(),
        default => $date->copy()->subDay(),
    };

    $nextDate = match ($view) {
        'week' => $date->copy()->addWeek(),
        'month' => $date->copy()->addMonth(),
        default => $date->copy()->addDay(),
    };

    $queryBase = collect(request()->query())->except('date')->all();

    $previousUrl = route('backoffice.agenda.index', array_merge($queryBase, ['date' => $previousDate->toDateString(), 'view' => $view]));
    $todayUrl = route('backoffice.agenda.index', array_merge($queryBase, ['date' => now()->toDateString(), 'view' => $view]));
    $nextUrl = route('backoffice.agenda.index', array_merge($queryBase, ['date' => $nextDate->toDateString(), 'view' => $view]));

    $items = [
        ['label' => 'Eventos', 'value' => $summary['total'] ?? 0],
        ['label' => 'Críticos', 'value' => $summary['critical'] ?? 0],
        ['label' => 'Alta prioridade', 'value' => $summary['high'] ?? 0],
        ['label' => 'Em atraso', 'value' => $summary['overdue'] ?? 0],
        ['label' => 'Hoje', 'value' => $summary['today'] ?? 0],
    ];
@endphp

<div class="rounded-[2rem] border border-slate-200/80 bg-white p-4 shadow-sm">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ($items as $item)
                <div class="rounded-2xl bg-slate-50 px-4 py-3">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $item['label'] }}</p>
                    <p class="mt-1 text-2xl font-black text-slate-950">{{ $item['value'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ $previousUrl }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">
                ← Anterior
            </a>

            <a href="{{ $todayUrl }}" class="rounded-2xl bg-blue-600 px-4 py-2 text-sm font-bold text-white hover:bg-blue-700">
                Hoje
            </a>

            <a href="{{ $nextUrl }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">
                Seguinte →
            </a>
        </div>
    </div>
</div>
