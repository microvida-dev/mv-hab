@props([
    'metrics' => [],
])

@php
    $available = (bool) ($metrics['available'] ?? false);
    $summary = $metrics['summary'] ?? [];
    $urgent = collect($metrics['urgent'] ?? []);
    $collapsedSummary = [
        ($summary['active_requests'] ?? 0).' ativo(s)',
        ($summary['submitted_requests'] ?? 0).' submetido(s)',
        ($summary['overdue_requests'] ?? 0).' vencido(s)',
    ];
@endphp

@if ($available)
    <x-dashboard.operations.expandable-panel
        id="correction-operations"
        eyebrow="Candidaturas"
        title="Aperfeiçoamentos em curso"
        description="Progresso agregado, prazos e submissões formais sem exposição do conteúdo documental."
        icon="document-warning"
        :summary="$collapsedSummary"
        :default-open="true"
    >
        <div class="p-5">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl bg-ink-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">
                        Ativos
                    </p>
                    <p class="mt-2 text-2xl font-semibold text-ink-950">
                        {{ $summary['active_requests'] ?? 0 }}
                    </p>
                </div>
                <div class="rounded-2xl bg-ink-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">
                        Submetidos
                    </p>
                    <p class="mt-2 text-2xl font-semibold text-ink-950">
                        {{ $summary['submitted_requests'] ?? 0 }}
                    </p>
                </div>
                <div class="rounded-2xl bg-ink-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">
                        Vencidos
                    </p>
                    <p class="mt-2 text-2xl font-semibold text-ink-950">
                        {{ $summary['overdue_requests'] ?? 0 }}
                    </p>
                </div>
                <div class="rounded-2xl bg-ink-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-500">
                        Checklist concluída
                    </p>
                    <p class="mt-2 text-2xl font-semibold text-ink-950">
                        {{ $summary['percentage'] ?? 100 }}%
                    </p>
                </div>
            </div>

            <div class="mt-5">
                <div class="flex items-center justify-between gap-4 text-sm">
                    <span class="font-semibold text-ink-700">
                        Progresso agregado
                    </span>
                    <span class="text-ink-500">
                        {{ $summary['completed_items'] ?? 0 }}
                        /
                        {{ $summary['total_items'] ?? 0 }}
                        elementos
                    </span>
                </div>
                <div class="mt-2 h-2 overflow-hidden rounded-full bg-ink-100">
                    <div
                        class="h-full rounded-full bg-mvhab-primary"
                        style="width: {{ $summary['percentage'] ?? 100 }}%"
                    ></div>
                </div>
            </div>

            @if ($urgent->isNotEmpty())
                <div class="mt-6 divide-y divide-ink-100 border-t border-ink-100">
                    @foreach ($urgent as $item)
                        <a
                            href="{{ $item['route'] }}"
                            class="flex flex-wrap items-center justify-between gap-4 py-4 transition hover:bg-ink-50"
                        >
                            <span>
                                <span class="block font-semibold text-ink-900">
                                    {{ $item['request_number'] }}
                                </span>
                                <span class="mt-1 block text-sm text-ink-500">
                                    {{ $item['status_label'] }}
                                    ·
                                    {{ $item['completed'] }}/{{ $item['total'] }}
                                    elementos
                                </span>
                            </span>
                            <span class="text-right">
                                <span class="block text-sm font-semibold text-ink-700">
                                    {{ $item['percentage'] }}%
                                </span>
                                <span class="mt-1 block text-xs text-ink-500">
                                    @if ($item['deadline'])
                                        {{ \Illuminate\Support\Carbon::parse($item['deadline'])->format('d/m/Y H:i') }}
                                    @elseif ($item['submitted_at'])
                                        Submetido em
                                        {{ \Illuminate\Support\Carbon::parse($item['submitted_at'])->format('d/m/Y H:i') }}
                                    @else
                                        Sem data
                                    @endif
                                </span>
                            </span>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="mt-6">
                    <x-ui.empty-state
                        title="Sem aperfeiçoamentos prioritários"
                        description="Não existem pedidos ativos, submetidos ou vencidos no âmbito autorizado."
                    />
                </div>
            @endif
        </div>
    </x-dashboard.operations.expandable-panel>
@endif
