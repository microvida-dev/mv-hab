@props([
    'term' => '',
    'groups' => [],
    'action' => route('backoffice.search.index'),
])

<x-ui.card>
    <form method="GET" action="{{ $action }}" class="space-y-4">
        <div>
            <label for="universal-search" class="text-sm font-semibold text-ink-900">Pesquisar</label>
            <div class="mt-2 flex flex-col gap-3 md:flex-row md:items-center">
                <input
                    id="universal-search"
                    name="q"
                    value="{{ $term }}"
                    type="search"
                    class="w-full rounded-md border-ink-200 text-sm shadow-sm focus:border-civic-600 focus:ring-civic-600"
                    placeholder="Pesquisar candidatura, concurso, contrato, tarefa, workspace ou comando..."
                    aria-describedby="universal-search-help"
                    autocomplete="off"
                >
                <x-ui.action-button type="submit" variant="primary" class="md:w-40">
                    <x-ui-icon name="search" class="h-4 w-4" />
                    <span>Pesquisar</span>
                </x-ui.action-button>
            </div>
            <p id="universal-search-help" class="mt-3 text-sm text-ink-500">
                Introduza pelo menos 2 caracteres para pesquisar dados operacionais. Os comandos rápidos continuam disponíveis por permissões.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <x-ui.action-button :href="route('backoffice.search.commands')" variant="secondary">
                <x-ui-icon name="bolt" class="h-4 w-4" />
                <span>Centro de Comandos</span>
            </x-ui.action-button>

            @foreach ($groups as $group)
                <x-ui.status-badge status="neutral" :label="$group['label']" />
            @endforeach
        </div>
    </form>
</x-ui.card>
