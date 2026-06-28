<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            eyebrow="Centro Analítico Municipal"
            title="Analytics executivos"
            description="KPIs, tendências, funil, SLA e carga operacional com dados agregados e minimizados."
        >
            <x-slot name="actions">
                <x-ui.action-button :href="route('backoffice.reports.index')">Relatórios</x-ui.action-button>
                @if (Auth::user()?->hasPermission('reports.view_executive'))
                    <x-ui.action-button :href="route('backoffice.reports.executive')" variant="primary">Painel executivo</x-ui.action-button>
                @endif
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-8">
        @include('backoffice.reports._filters')

        <x-analytics.executive-card :summary="$analytics['summary']" />

        <section class="space-y-4" aria-labelledby="analytics-kpis-heading">
            <x-ui.section-header
                id="analytics-kpis-heading"
                title="KPIs executivos"
                description="Indicadores agregados por perfil e permissões, sem exposição de dados pessoais."
            />

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @forelse (($analytics['kpis'] ?? []) as $metric)
                    <x-analytics.kpi-card :metric="$metric" />
                @empty
                    <x-analytics.analytics-empty-state class="md:col-span-2 xl:col-span-4" />
                @endforelse
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-2" aria-label="Tendências operacionais">
            @foreach (($analytics['trends'] ?? []) as $trend)
                <x-analytics.trend-card :dataset="$trend" />
            @endforeach
        </section>

        <section class="grid gap-6 xl:grid-cols-2" aria-label="Distribuições operacionais">
            @foreach (($analytics['charts'] ?? []) as $chart)
                @if (($chart['type'] ?? null) === 'donut')
                    <x-analytics.donut-chart :dataset="$chart" />
                @else
                    <x-analytics.bar-chart :dataset="$chart" />
                @endif
            @endforeach
        </section>

        <section class="grid gap-6 xl:grid-cols-2" aria-label="Funil e SLA">
            <x-analytics.funnel-chart :steps="$analytics['funnel'] ?? []" />
            <x-analytics.sla-summary :sla="$analytics['sla'] ?? []" />
        </section>

        <section class="grid gap-6 xl:grid-cols-2" aria-label="Carga e tabelas analíticas">
            <x-analytics.workload-summary :items="$analytics['workload'] ?? []" />
            <x-ui.card class="space-y-4">
                <x-ui.section-header
                    title="Tabelas analíticas"
                    description="Resumo paginado e agregado para leitura operacional."
                />
                <x-analytics.analytics-table
                    :rows="$analytics['tables']['applications_by_contest'] ?? []"
                    caption="Candidaturas agregadas por concurso"
                />
                <x-analytics.analytics-table
                    :rows="$analytics['tables']['operations'] ?? []"
                    caption="Resumo operacional por domínio"
                />
            </x-ui.card>
        </section>

        <x-ui.card class="space-y-3">
            <h2 class="font-semibold text-ink-900">Métricas omitidas por RGPD</h2>
            <ul class="space-y-2 text-sm text-ink-600">
                @foreach (($analytics['omitted_metrics'] ?? []) as $omitted)
                    <li class="flex gap-2">
                        <span aria-hidden="true" class="mt-2 h-1.5 w-1.5 rounded-full bg-ink-400"></span>
                        <span>{{ $omitted }}</span>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    </div>
</x-app-layout>
