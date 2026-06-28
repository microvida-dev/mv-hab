<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            eyebrow="Centro de Operações Municipal da Habitação"
            title="Pesquisa Universal"
            description="Resultados autorizados, agrupados por área funcional e com dados minimizados."
        >
            <x-slot name="actions">
                <x-ui.action-button :href="route('dashboard')">
                    <x-ui-icon name="dashboard" class="h-4 w-4" />
                    <span>Painel Principal</span>
                </x-ui.action-button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="py-8">
        <div class="mv-page-shell">
            <x-search.universal-search :term="$term" />

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
                <x-search.search-dialog title="Resultados">
                    <div class="space-y-6">
                        @forelse (($search['groups'] ?? []) as $group)
                            <x-search.search-result-group :group="$group" />
                        @empty
                            <x-search.empty-results :term="$term" />
                        @endforelse
                    </div>
                </x-search.search-dialog>

                <x-search.command-palette :commands="$commands" />
            </div>
        </div>
    </div>
</x-app-layout>
