<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            eyebrow="Centro de Operações Municipal da Habitação"
            title="Centro de Comandos"
            description="Atalhos não destrutivos disponíveis para o seu perfil e permissões."
        >
            <x-slot name="actions">
                <x-ui.action-button :href="route('backoffice.search.index')">
                    <x-ui-icon name="search" class="h-4 w-4" />
                    <span>Pesquisa Universal</span>
                </x-ui.action-button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="py-8">
        <div class="mv-page-shell">
            <x-search.universal-search :term="$term" :action="route('backoffice.search.commands')" />
            <x-search.command-palette :commands="$commands" />
        </div>
    </div>
</x-app-layout>
