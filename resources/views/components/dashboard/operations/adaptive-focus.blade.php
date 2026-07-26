@props([
    'adaptiveDashboard' => [],
])

@php
    $profileLabel = data_get($adaptiveDashboard, 'profile_label', 'Backoffice municipal');
    $headline = data_get($adaptiveDashboard, 'headline', 'Foco operacional');
    $description = data_get($adaptiveDashboard, 'description');
    $icon = data_get($adaptiveDashboard, 'icon', 'dashboard');
    $riskLevel = data_get($adaptiveDashboard, 'risk_level', 'success');
    $riskLabel = data_get($adaptiveDashboard, 'risk_label', 'Operação estável');
    $workspaceLabel = data_get($adaptiveDashboard, 'primary_workspace_label');
    $primaryAction = data_get($adaptiveDashboard, 'primary_action');
    $focusMetrics = collect(data_get($adaptiveDashboard, 'focus_metrics', []));
    $priorityWidgets = collect(data_get($adaptiveDashboard, 'priority_widgets', []));

    $riskClasses = [
        'danger' => 'bg-red-50 text-red-700 ring-red-200',
        'warning' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'neutral' => 'bg-ink-50 text-ink-700 ring-ink-200',
    ][$riskLevel] ?? 'bg-ink-50 text-ink-700 ring-ink-200';
@endphp

<section class="mv-card p-5">
    <div class="grid gap-5 xl:grid-cols-[minmax(0,1.5fr)_minmax(18rem,0.8fr)]">
        <div class="flex items-start gap-4">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-mvhab-surface text-mvhab-primary">
                <x-mv-icon :name="$icon" size="md" />
            </span>

            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-mvhab-primary/10 px-2.5 py-1 text-[11px] font-bold text-mvhab-primary">
                        {{ $profileLabel }}
                    </span>

                    <span class="rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset {{ $riskClasses }}">
                        {{ $riskLabel }}
                    </span>
                </div>

                <h2 class="mt-3 text-lg font-semibold text-ink-950">
                    {{ $headline }}
                </h2>

                @if ($description)
                    <p class="mt-1 text-sm leading-6 text-ink-600">
                        {{ $description }}
                    </p>
                @endif

                <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold text-ink-600">
                    @if ($workspaceLabel)
                        <span class="rounded-full bg-ink-50 px-3 py-1.5">
                            Workspace recomendado: {{ $workspaceLabel }}
                        </span>
                    @endif

                    <span class="rounded-full bg-ink-50 px-3 py-1.5">
                        {{ $focusMetrics->count() }} indicador(es) em foco
                    </span>

                    <span class="rounded-full bg-ink-50 px-3 py-1.5">
                        {{ $priorityWidgets->count() }} widget(s) prioritário(s)
                    </span>
                </div>
            </div>
        </div>

        <div class="rounded-3xl border border-ink-100 bg-ink-50/60 p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-ink-500">
                Ação principal
            </p>

            @if (is_array($primaryAction))
                <h3 class="mt-2 text-sm font-semibold text-ink-950">
                    {{ data_get($primaryAction, 'label') }}
                </h3>

                @if (data_get($primaryAction, 'description'))
                    <p class="mt-1 text-xs leading-5 text-ink-500">
                        {{ data_get($primaryAction, 'description') }}
                    </p>
                @endif

                @if (data_get($primaryAction, 'href'))
                    <a
                        href="{{ data_get($primaryAction, 'href') }}"
                        class="mt-4 inline-flex items-center gap-2 text-xs font-bold text-mvhab-primary transition hover:text-mvhab-primary-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-mvhab-primary"
                    >
                        Abrir prioridade
                        <span aria-hidden="true">→</span>
                    </a>
                @endif
            @else
                <p class="mt-2 text-sm text-ink-500">
                    Sem ação prioritária disponível para este perfil.
                </p>
            @endif
        </div>
    </div>

    @if ($focusMetrics->isNotEmpty())
        <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($focusMetrics as $metric)
                <x-dashboard.kpi-card :metric="$metric" />
            @endforeach
        </div>
    @endif
</section>
