@php
    $metricPayload = $dashboard['metrics'] ?? [];
    $metrics = [
        ['label' => 'Candidaturas', 'value' => data_get($metricPayload, 'applications.total', 0), 'description' => 'Total no âmbito filtrado.'],
        ['label' => 'Ação pendente', 'value' => data_get($metricPayload, 'applications.pending_action', 0), 'description' => 'Submetidas, em análise ou com aperfeiçoamento.'],
        ['label' => 'Documentos pendentes', 'value' => data_get($metricPayload, 'documents.pending', 0), 'description' => 'Submetidos ou em revisão.'],
        ['label' => 'Alertas abertos', 'value' => data_get($metricPayload, 'alerts.open', 0), 'description' => 'Alertas operacionais por tratar.'],
    ];
    $deadlines = data_get($metricPayload, 'deadlines.items', data_get($metricPayload, 'deadlines', []));
    $applicationsByStatus = data_get($metricPayload, 'applications.by_status', []);
    $alerts = data_get($dashboard, 'work_queue.alerts_assigned', collect());
    $visits = data_get($metricPayload, 'visits', []);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-mvhab-primary">Backoffice operacional</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Painel operacional</h1>
            <p class="mt-1 text-sm text-ink-500">Acompanhamento diário de prazos, candidaturas, documentos, alertas e visitas.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mv-surface px-4 py-3 text-sm font-semibold text-mvhab-primary">{{ session('success') }}</div>
            @endif

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($metrics as $metric)
                    <div class="mv-surface p-5">
                        <p class="text-sm font-semibold text-ink-500">{{ $metric['label'] ?? 'Indicador' }}</p>
                        <p class="mt-3 text-3xl font-semibold text-ink-900">{{ is_numeric($metric['value'] ?? null) ? number_format((float) $metric['value'], 0, ',', '.') : ($metric['value'] ?? '—') }}</p>
                        @if (! empty($metric['description']))
                            <p class="mt-2 text-xs leading-5 text-ink-500">{{ $metric['description'] }}</p>
                        @endif
                    </div>
                @endforeach
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <div class="mv-surface p-6">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-semibold text-ink-900">Prazos críticos</h2>
                        <a href="{{ route('backoffice.internal-alerts.index') }}" class="text-sm font-semibold text-mvhab-primary">Ver alertas</a>
                    </div>
                    <div class="mt-4 divide-y divide-ink-100">
                        @forelse ($deadlines as $key => $deadline)
                            <div class="py-3 text-sm">
                                <div class="flex justify-between gap-4">
                                    <span class="font-semibold text-ink-900">{{ is_array($deadline) ? ($deadline['label'] ?? 'Prazo') : ucfirst((string) $key) }}</span>
                                    <span class="text-ink-500">{{ is_array($deadline) ? ($deadline['date'] ?? '—') : $deadline }}</span>
                                </div>
                                <p class="mt-1 text-ink-500">{{ is_array($deadline) ? ($deadline['context'] ?? '') : 'Resumo de prazos do procedimento.' }}</p>
                            </div>
                        @empty
                            <p class="py-6 text-sm text-ink-500">Sem prazos críticos no período analisado.</p>
                        @endforelse
                    </div>
                </div>

                <div class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Volume por estado</h2>
                    <div class="mt-4 overflow-x-auto">
                        <table class="mv-table">
                            <thead><tr><th>Estado</th><th>Total</th></tr></thead>
                            <tbody>
                                @forelse ($applicationsByStatus as $status => $total)
                                    <tr>
                                        <td>{{ \App\Enums\ApplicationStatus::tryFrom((string) $status)?->label() ?? $status }}</td>
                                        <td>{{ $total }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2">Sem candidaturas registadas.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <div class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Alertas recentes</h2>
                    <div class="mt-4 divide-y divide-ink-100">
                        @forelse ($alerts as $alert)
                            <a href="{{ route('backoffice.internal-alerts.show', $alert) }}" class="block py-3 text-sm">
                                <span class="font-semibold text-ink-900">{{ $alert->title }}</span>
                                <span class="ml-2 rounded-2xl bg-ink-100 px-2 py-0.5 text-xs text-ink-600">{{ $alert->severity->label() }}</span>
                                <p class="mt-1 text-ink-500">{{ $alert->message }}</p>
                            </a>
                        @empty
                            <p class="py-6 text-sm text-ink-500">Sem alertas operacionais recentes.</p>
                        @endforelse
                    </div>
                </div>

                <div class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Visitas e atendimento</h2>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach ($visits as $label => $value)
                            <div class="mv-surface p-4">
                                <p class="text-sm text-ink-500">{{ $label }}</p>
                                <p class="mt-2 text-2xl font-semibold text-ink-900">{{ $value }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
