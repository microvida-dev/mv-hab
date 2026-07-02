@props(['filters' => [], 'options' => []])

@php
    $view = $filters['view'] ?? 'day';
    $date = $filters['date'] ?? now()->toDateString();
@endphp

<div class="rounded-[2rem] border border-slate-200/80 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-6">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-blue-600">Agenda Municipal</p>
                <h1 class="mt-2 max-w-2xl text-3xl font-bold tracking-tight text-slate-950">
                    Operação diária e planeamento municipal
                </h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                    Centralize visitas, vistorias, prazos, audiências, reclamações e tarefas operacionais.
                </p>
            </div>

            <div class="rounded-2xl bg-slate-50 px-4 py-3 text-right">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Data ativa</p>
                <p class="mt-1 text-sm font-bold text-slate-900">
                    {{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('d F Y') }}
                </p>
            </div>
        </div>

        <form method="GET" action="{{ route('backoffice.agenda.index') }}" class="grid gap-3 lg:grid-cols-[130px_160px_1fr_140px_140px_120px_120px]">
            <select name="view" class="rounded-2xl border-slate-200 bg-slate-50 text-sm font-medium">
                @foreach ($options['views'] ?? [] as $option)
                    <option value="{{ $option->value }}" @selected($view === $option->value)>
                        {{ match($option->value) { 'day' => 'Dia', 'week' => 'Semana', 'month' => 'Mês', default => $option->value } }}
                    </option>
                @endforeach
            </select>

            <input type="date" name="date" value="{{ $date }}" class="rounded-2xl border-slate-200 bg-slate-50 text-sm font-medium">

            <select name="workspace" class="rounded-2xl border-slate-200 bg-slate-50 text-sm font-medium">
                <option value="">Todos os workspaces</option>
                @foreach ($options['workspaces'] ?? [] as $option)
                    <option value="{{ $option->value }}" @selected(($filters['workspace'] ?? null) === $option->value)>
                        {{ ucfirst($option->value) }}
                    </option>
                @endforeach
            </select>

            <select name="priority" class="rounded-2xl border-slate-200 bg-slate-50 text-sm font-medium">
                <option value="">Todas prioridades</option>
                @foreach ($options['priorities'] ?? [] as $option)
                    <option value="{{ $option->value }}" @selected(($filters['priority'] ?? null) === $option->value)>
                        {{ ucfirst($option->value) }}
                    </option>
                @endforeach
            </select>

            <select name="type" class="rounded-2xl border-slate-200 bg-slate-50 text-sm font-medium">
                <option value="">Todos os tipos</option>
                @foreach ($options['types'] ?? [] as $option)
                    <option value="{{ $option->value }}" @selected(($filters['type'] ?? null) === $option->value)>
                        {{ str($option->value)->replace('-', ' ')->title() }}
                    </option>
                @endforeach
            </select>

            <input
                type="number"
                name="technician"
                value="{{ $filters['technician'] ?? '' }}"
                placeholder="Técnico ID"
                class="rounded-2xl border-slate-200 bg-slate-50 text-sm font-medium"
            >

            <button type="submit" class="rounded-2xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700">
                Filtrar
            </button>
        </form>
    </div>
</div>
