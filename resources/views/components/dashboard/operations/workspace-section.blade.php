@props([
    'workspaces' => [],
    'favorites' => [],
])

<x-dashboard.operations.expandable-panel
    id="workspaces"
    eyebrow="Navegação"
    title="Espaços de Trabalho"
    description="Cada espaço de trabalho agrupa apenas os módulos permitidos pelo seu perfil."
    icon="dashboard"
    :default-open="false"
    :summary="[
        count($workspaces).' espaço(s)',
        count($favorites).' favorito(s)',
    ]"
>
    <div class="p-5">
        <x-navigation.workspace-grid :workspaces="$workspaces" :favorites="$favorites" />
    </div>
</x-dashboard.operations.expandable-panel>
