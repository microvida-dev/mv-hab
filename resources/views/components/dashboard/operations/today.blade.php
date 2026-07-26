@props([
    'items' => [],
    'timeline' => [],
])

@php
    $nextAction = $timeline['nextAction'] ?? null;
    $groups = $timeline['groups'] ?? [];
    $fallbackItems = $items ?? [];
    $eventsCount = collect($groups)->sum(fn ($group) => count($group['items'] ?? []));
    $totalCount = $eventsCount > 0 ? $eventsCount : count($fallbackItems);
@endphp

<x-dashboard.operations.expandable-panel
    id="today"
    eyebrow="Operação"
    title="Hoje"
    description="Atividade operacional agregada para o dia."
    icon="calendar"
    :summary="[
        $totalCount.' evento(s)',
        $nextAction ? 'Próxima ação definida' : 'Sem próxima ação',
    ]"
>
    @if($nextAction)
        <div class="p-5">
            <div class="rounded-3xl border border-mvhab-primary/20 bg-mvhab-primary/5 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-mvhab-primary">
                    Próxima ação recomendada
                </p>

                <div class="mt-3 flex items-start gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-mvhab-primary">
                        <x-mv-icon :name="$nextAction['icon'] ?? 'calendar'" size="sm" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-ink-950">
                            {{ $nextAction['title'] ?? 'Ação pendente' }}
                        </p>

                        @if(!empty($nextAction['description']))
                            <p class="mt-1 text-sm text-ink-600">
                                {{ $nextAction['description'] }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="px-5 pb-5">
        <x-dashboard.operations.timeline
            :groups="$groups"
            :fallback-items="$fallbackItems"
        />
    </div>
</x-dashboard.operations.expandable-panel>
