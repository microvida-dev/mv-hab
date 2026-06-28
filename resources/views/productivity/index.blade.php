<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            eyebrow="Centro de Operações Municipal"
            title="Produtividade"
            description="Centro de trabalho, prioridades, caixa de entrada e carga operacional agregados a partir de dados autorizados."
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
            <x-flash-message />

            <x-navigation.breadcrumbs />

            <x-productivity.next-case :next-case="$productivity['next_case'] ?? null" />

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
                <div class="space-y-6">
                    <x-productivity.action-center :sections="$productivity['action_center'] ?? []" />
                    <x-productivity.my-work :groups="$productivity['my_work'] ?? []" />
                    <x-productivity.smart-queue :queues="$productivity['smart_queue'] ?? []" />
                    <x-productivity.batch-toolbar :toolbar="$productivity['batch_toolbar'] ?? ['actions' => []]" />
                </div>

                <aside class="space-y-6">
                    <x-productivity.inbox :groups="$productivity['inbox'] ?? []" />
                    <x-productivity.workload :items="$productivity['workload'] ?? []" />
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
