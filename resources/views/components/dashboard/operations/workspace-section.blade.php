@props([
    'workspaces' => [],
    'favorites' => [],
])

<section>
    <x-ui.section-header
        class="mb-4"
        title="Espaços de Trabalho"
        description="Cada espaço de trabalho agrupa apenas os módulos permitidos pelo seu perfil."
    />

    <x-navigation.workspace-grid :workspaces="$workspaces" :favorites="$favorites" />
</section>
