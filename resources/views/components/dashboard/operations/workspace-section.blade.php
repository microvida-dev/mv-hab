@props([
    'workspaces' => [],
    'favorites' => [],
    'workspaceIntelligence' => [],
])

@php
    $summary = data_get($workspaceIntelligence, 'summary', []);
    $preferred = data_get($workspaceIntelligence, 'preferred');
    $intelligentWorkspaces = collect(data_get($workspaceIntelligence, 'workspaces', []));
@endphp

<x-dashboard.operations.expandable-panel
    id="workspaces"
    eyebrow="Navegação"
    title="Espaços de Trabalho"
    description="Cada espaço de trabalho agrupa apenas os módulos permitidos pelo seu perfil."
    icon="dashboard"
    :default-open="false"
    :summary="[
        data_get($summary, 'preferred_label') ? 'Inicial: '.data_get($summary, 'preferred_label') : count($workspaces).' espaço(s)',
        data_get($summary, 'favorites', count($favorites)).' favorito(s)',
        data_get($summary, 'recent_items', 0).' recente(s)',
    ]"
>
    <div class="space-y-5 p-5">
        @if ($preferred)
            <div>
                <p class="mb-2 text-xs font-bold uppercase tracking-wide text-mvhab-primary">
                    Espaço inicial
                </p>

                <x-dashboard.workspaces.intelligent-card
                    :workspace="$preferred"
                    featured
                />
            </div>
        @endif

        <div>
            <div class="mb-3 flex items-center justify-between gap-3">
                <p class="text-xs font-bold uppercase tracking-wide text-ink-500">
                    Todos os espaços autorizados
                </p>

                <span class="text-xs font-semibold text-ink-400">
                    {{ count($workspaces) }} disponíveis
                </span>
            </div>

            @if ($intelligentWorkspaces->isNotEmpty())
                <div class="grid gap-3 lg:grid-cols-2">
                    @foreach ($intelligentWorkspaces as $workspace)
                        <x-dashboard.workspaces.intelligent-card :workspace="$workspace" />
                    @endforeach
                </div>
            @else
                <x-navigation.workspace-grid :workspaces="$workspaces" :favorites="$favorites" />
            @endif
        </div>
    </div>
</x-dashboard.operations.expandable-panel>
