<x-app-layout>
    <x-slot name="header"><div><h1 class="text-xl font-semibold text-ink-900">Relatórios e indicadores</h1><p class="mt-1 text-sm text-ink-500">Consulta operacional, executiva e exportações auditadas.</p></div></x-slot>
    <div class="space-y-8">
        <nav class="flex flex-wrap gap-3">
            <a class="mv-button-primary" href="{{ route('backoffice.analytics.index') }}">Centro analítico</a>
            <a class="mv-button-primary" href="{{ route('backoffice.reports.operational') }}">Painel operacional</a>
            @if (Auth::user()->hasPermission('reports.view_executive'))<a class="mv-button-secondary" href="{{ route('backoffice.reports.executive') }}">Painel executivo</a>@endif
            <a class="mv-button-secondary" href="{{ route('backoffice.reports.runs.index') }}">Execuções</a>
            <a class="mv-button-secondary" href="{{ route('backoffice.reports.exports.index') }}">Exportações</a>
            <a class="mv-button-secondary" href="{{ route('backoffice.reports.filter-presets.index') }}">Filtros guardados</a>
        </nav>
        <section>
            <h2 class="text-lg font-semibold text-ink-900">Catálogo disponível</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($reports as $report)
                    <a href="{{ route('backoffice.reports.definitions.show', $report) }}" class="mv-card block transition hover:border-mvhab-support">
                        <div class="flex items-start justify-between gap-3"><h3 class="font-semibold text-ink-900">{{ $report->name }}</h3><span class="mv-badge">{{ $report->report_type->label() }}</span></div>
                        <p class="mt-2 text-sm text-ink-600">{{ $report->description }}</p>
                        <p class="mt-4 text-xs font-medium text-ink-500">{{ $report->sensitivity_level->label() }}</p>
                    </a>
                @empty
                    <p class="text-sm text-ink-500">Ainda não existem definições de relatório ativas.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
