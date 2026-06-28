<x-app-layout>
    <x-slot name="header">
        <div class="space-y-1">
            <x-navigation.breadcrumbs />
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mv-page-shell">
            <x-flash-message />

            <x-cases.enterprise.layout :workspace="$workspace">
                @foreach ($workspace['tabs'] as $tab)
                    @switch($tab['key'])
                        @case('summary')
                            <x-cases.enterprise.summary-panel :items="$workspace['summary_items']" />
                            @break

                        @case('timeline')
                            <x-cases.enterprise.timeline :items="$workspace['timeline']" />
                            @break

                        @case('checklist')
                            <x-cases.enterprise.checklist :items="$workspace['checklist']" />
                            @break

                        @case('documents')
                            <x-cases.enterprise.documents :documents="$workspace['documents']" />
                            @break

                        @case('relations')
                            <x-cases.enterprise.relations :relations="$workspace['relations']" />
                            @break

                        @case('communications')
                            <x-cases.enterprise.communications :communications="$workspace['communications']" />
                            @break

                        @case('tasks')
                            <x-cases.enterprise.tasks :tasks="$workspace['tasks']" />
                            @break

                        @case('history')
                            <x-cases.enterprise.history :items="$workspace['history']" />
                            @break

                        @default
                            <x-ui.card :id="'case-tab-'.$tab['key']">
                                <x-ui.section-header :title="$tab['label']" />
                                <x-cases.enterprise.empty-state
                                    title="Secção preparada"
                                    description="Este separador está disponível para o tipo de caso, mas não tem dados autorizados neste momento."
                                />
                            </x-ui.card>
                    @endswitch
                @endforeach
            </x-cases.enterprise.layout>
        </div>
    </div>
</x-app-layout>
