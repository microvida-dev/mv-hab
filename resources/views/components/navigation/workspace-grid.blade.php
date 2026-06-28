@props([
    'workspaces' => [],
    'favorites' => [],
])

@php
    $favoriteWorkspaceKeys = collect($favorites)->pluck('workspace_key');
@endphp

<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
    @forelse ($workspaces as $workspace)
        <x-navigation.workspace-card
            :workspace="$workspace"
            :is-favorite="$favoriteWorkspaceKeys->contains($workspace['key'])"
        />
    @empty
        <x-ui.empty-state
            title="Sem espaços de trabalho disponíveis"
            description="Não existem espaços de trabalho disponíveis para o seu perfil."
        />
    @endforelse
</div>
