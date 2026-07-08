@props([
    'queue' => [],
])

@php
    $items = collect(data_get($queue, 'items', []));
    $summary = data_get($queue, 'summary', []);

    $priorityClasses = [
        'critical' => 'bg-red-50 text-red-700 ring-red-200',
        'high' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'medium' => 'bg-sky-50 text-sky-700 ring-sky-200',
        'low' => 'bg-ink-50 text-ink-700 ring-ink-200',
    ];
@endphp

<x-dashboard.operations.expandable-panel
    id="priority-queue"
    eyebrow="Prioridades"
    title="Fila operacional prioritária"
    description="Itens que devem ser tratados primeiro com base em prazos, indicadores, widgets e ações do perfil."
    icon="dashboard"
    :summary="[
        data_get($summary, 'label', $items->count().' prioridade(s)'),
        data_get($summary, 'critical', 0).' crítica(s)',
        data_get($summary, 'high', 0).' alta(s)',
    ]"
>
    <div class="divide-y divide-ink-100">
        @forelse ($items as $item)
            @php
                $priority = data_get($item, 'priority', 'low');
                $classes = $priorityClasses[$priority] ?? $priorityClasses['low'];
            @endphp

            <article class="px-5 py-4">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-mvhab-surface text-mvhab-primary">
                        <x-mv-icon :name="data_get($item, 'icon', 'dashboard')" size="sm" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-ink-50 px-2.5 py-1 text-[11px] font-bold text-ink-500">
                                        {{ data_get($item, 'source', 'Operação') }}
                                    </span>

                                    <span class="rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 ring-inset {{ $classes }}">
                                        {{ match ($priority) {
                                            'critical' => 'Crítica',
                                            'high' => 'Alta',
                                            'medium' => 'Média',
                                            default => 'Baixa',
                                        } }}
                                    </span>
                                </div>

                                <h3 class="mt-2 text-sm font-semibold text-ink-950">
                                    {{ data_get($item, 'title', 'Prioridade operacional') }}
                                </h3>

                                @if (data_get($item, 'description'))
                                    <p class="mt-1 text-xs leading-5 text-ink-500">
                                        {{ data_get($item, 'description') }}
                                    </p>
                                @endif
                            </div>

                            @if (! is_null(data_get($item, 'count')))
                                <strong class="shrink-0 text-2xl font-bold text-ink-900">
                                    {{ data_get($item, 'count') }}
                                </strong>
                            @endif
                        </div>

                        @if (data_get($item, 'href'))
                            <a
                                href="{{ data_get($item, 'href') }}"
                                class="mt-3 inline-flex items-center gap-2 text-xs font-bold text-mvhab-primary transition hover:text-mvhab-primary-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-mvhab-primary"
                            >
                                {{ data_get($item, 'cta', 'Abrir') }}
                                <span aria-hidden="true">→</span>
                            </a>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="p-5">
                <x-dashboard.empty-state
                    title="Sem prioridades ativas"
                    description="Não existem prazos, alertas ou ações prioritárias para apresentar."
                />
            </div>
        @endforelse
    </div>
</x-dashboard.operations.expandable-panel>
