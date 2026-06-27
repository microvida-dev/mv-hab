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
        <div class="rounded-md border border-ink-100 bg-white p-5 text-sm text-ink-500">
            Não existem workspaces disponíveis para o seu perfil.
        </div>
    @endforelse
</div>
