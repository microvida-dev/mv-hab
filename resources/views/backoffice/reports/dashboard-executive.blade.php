<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            eyebrow="Centro Analítico Municipal"
            title="Painel executivo"
            description="Visão agregada para decisão municipal, com gráficos acessíveis e dados minimizados."
        >
            <x-slot name="actions">
                <x-ui.action-button :href="route('backoffice.analytics.index')" variant="primary">Centro analítico</x-ui.action-button>
                <x-ui.action-button :href="route('backoffice.reports.index')">Relatórios</x-ui.action-button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-8">
        @include('backoffice.reports._filters')

        <x-analytics.executive-card :summary="$analytics['summary']" />

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach (($analytics['kpis'] ?? []) as $metric)
                <x-analytics.kpi-card :metric="$metric" />
            @endforeach
        </div>

        <section class="grid gap-6 xl:grid-cols-2" aria-label="Tendência e funil executivo">
            @foreach (($analytics['trends'] ?? []) as $trend)
                <x-analytics.trend-card :dataset="$trend" />
            @endforeach
            <x-analytics.funnel-chart :steps="$analytics['funnel'] ?? []" class="xl:col-span-2" />
        </section>

        <section><h2 class="text-lg font-semibold text-ink-900">Candidaturas por estado</h2><div class="mt-3 overflow-x-auto"><table class="mv-table"><thead><tr><th>Estado</th><th>Total</th></tr></thead><tbody>@foreach ($by_status as $status => $total)<tr><td>{{ \App\Enums\ApplicationStatus::tryFrom($status)?->label() ?? $status }}</td><td>{{ $total }}</td></tr>@endforeach</tbody></table></div></section>
        <section><h2 class="text-lg font-semibold text-ink-900">Resumo por concurso</h2><div class="mt-3 overflow-x-auto"><table class="mv-table"><thead><tr><th>Programa</th><th>Concurso</th><th>Estado</th><th>Total</th></tr></thead><tbody>@foreach ($by_contest as $row)<tr>@foreach ($row as $value)<td>{{ $value }}</td>@endforeach</tr>@endforeach</tbody></table></div></section>
    </div>
</x-app-layout>
