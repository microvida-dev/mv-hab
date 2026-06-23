@php
    $summary = [
        ['label' => 'Candidaturas', 'value' => data_get($metrics, 'applications.total', 0), 'trend' => data_get($metrics, 'applications.submitted_month', 0).' submetidas este mês'],
        ['label' => 'Documentos pendentes', 'value' => data_get($metrics, 'documents.pending', 0), 'trend' => 'Revisão documental'],
        ['label' => 'Listas pendentes', 'value' => data_get($metrics, 'lists.provisional_pending', 0) + data_get($metrics, 'lists.definitive_pending', 0), 'trend' => 'Provisórias e definitivas'],
        ['label' => 'Alertas críticos', 'value' => data_get($metrics, 'alerts.critical', 0), 'trend' => 'Risco operacional'],
    ];
    $contestOverview = collect(data_get($metrics, 'applications.by_contest', []))
        ->map(fn ($row) => [
            'title' => $row['title'] ?? 'Sem concurso',
            'status' => 'Em acompanhamento',
            'applications_count' => $row['total'] ?? 0,
        ])
        ->all();
    $riskIndicators = [
        ['label' => 'Candidaturas com ação pendente', 'value' => data_get($metrics, 'applications.pending_action', 0), 'description' => 'Volume em estados que requerem tratamento municipal.'],
        ['label' => 'Alertas atribuídos ao utilizador', 'value' => data_get($metrics, 'alerts.assigned_to_me', 0), 'description' => 'Tarefas críticas sob responsabilidade direta.'],
        ['label' => 'Atas pendentes de revisão', 'value' => data_get($metrics, 'minutes.pending_review', 0), 'description' => 'Atas geradas que ainda aguardam validação.'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Backoffice operacional</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Dashboard executivo</h1>
            <p class="mt-1 text-sm text-ink-500">Síntese municipal para acompanhamento de concursos, decisão e execução.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($summary as $metric)
                    <div class="mv-surface p-5">
                        <p class="text-sm font-semibold text-ink-500">{{ $metric['label'] ?? 'Indicador' }}</p>
                        <p class="mt-3 text-3xl font-semibold text-ink-900">{{ is_numeric($metric['value'] ?? null) ? number_format((float) $metric['value'], 0, ',', '.') : ($metric['value'] ?? '—') }}</p>
                        @if (! empty($metric['trend']))
                            <p class="mt-2 text-xs text-ink-500">{{ $metric['trend'] }}</p>
                        @endif
                    </div>
                @endforeach
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <div class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Concursos em acompanhamento</h2>
                    <div class="mt-4 overflow-x-auto">
                        <table class="mv-table">
                            <thead><tr><th>Concurso</th><th>Estado</th><th>Candidaturas</th></tr></thead>
                            <tbody>
                                @forelse ($contestOverview as $row)
                                    <tr>
                                        <td>{{ $row['title'] ?? '—' }}</td>
                                        <td>{{ $row['status'] ?? '—' }}</td>
                                        <td>{{ $row['applications_count'] ?? 0 }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3">Sem concursos no período.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Riscos operacionais</h2>
                    <div class="mt-4 divide-y divide-ink-100">
                        @forelse ($riskIndicators as $risk)
                            <div class="py-3 text-sm">
                                <div class="flex justify-between gap-4">
                                    <span class="font-semibold text-ink-900">{{ $risk['label'] ?? 'Risco' }}</span>
                                    <span class="text-ink-500">{{ $risk['value'] ?? '—' }}</span>
                                </div>
                                <p class="mt-1 text-ink-500">{{ $risk['description'] ?? '' }}</p>
                            </div>
                        @empty
                            <p class="py-6 text-sm text-ink-500">Sem riscos críticos detetados.</p>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
