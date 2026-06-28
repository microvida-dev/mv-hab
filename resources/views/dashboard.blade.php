<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            eyebrow="Centro de Operações Municipal da Habitação"
            title="Painel Principal"
            description="Aceda aos workspaces disponíveis para o seu perfil e continue a operação municipal a partir de áreas funcionais."
        >
            <x-slot name="actions">
                <x-ui.action-button :href="route('public.portal')">
                    <x-ui-icon name="home" class="h-4 w-4" />
                    <span>Portal Público</span>
                </x-ui.action-button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="py-8">
        <div class="mv-page-shell">
            <x-flash-message />

            <x-ui.card>
                <label for="global-search" class="text-sm font-semibold text-ink-900">Pesquisar</label>
                <div class="mt-2 flex flex-col gap-3 md:flex-row md:items-center">
                    <input
                        id="global-search"
                        type="search"
                        class="w-full rounded-md border-ink-200 text-sm shadow-sm focus:border-civic-600 focus:ring-civic-600"
                        placeholder="Pesquisar munícipe, concurso, contrato, candidatura, documento, relatório, fogo ou Work Task..."
                        aria-describedby="global-search-help"
                    >
                    <x-ui.action-button type="button" variant="primary" class="md:w-40" disabled>
                        Preparado
                    </x-ui.action-button>
                </div>
                <p id="global-search-help" class="mt-3 text-sm text-ink-500">
                    A pesquisa universal fica preparada nesta fundação e será ativada por fonte de dados nas próximas iterações.
                </p>

                @if ($searchGroups !== [])
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($searchGroups as $group)
                            <x-ui.status-badge status="neutral" :label="$group['label']" />
                        @endforeach
                    </div>
                @endif
            </x-ui.card>

            <x-dashboard.profile-dashboard :dashboard="$dashboard" />

            <section>
                <x-ui.section-header
                    class="mb-4"
                    title="Indicadores do perfil"
                    description="Contagens agregadas e autorizadas para orientar a operação diária."
                />

                @if (($dashboard['metrics'] ?? []) !== [])
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        @foreach ($dashboard['metrics'] as $metric)
                            <x-dashboard.kpi-card :metric="$metric" />
                        @endforeach
                    </div>
                @else
                    <x-dashboard.empty-state
                        title="Sem indicadores disponíveis"
                        description="Não existem KPIs autorizados ou dados operacionais para apresentar neste momento."
                    />
                @endif
            </section>

            <section>
                <x-ui.section-header
                    class="mb-4"
                    title="Workspaces"
                    description="Cada workspace agrupa apenas os módulos permitidos pelo seu perfil."
                />

                <x-navigation.workspace-grid :workspaces="$workspaces" :favorites="$favorites" />
            </section>

            <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
                <div class="space-y-6">
                    <section class="mv-card">
                        <div class="border-b border-ink-100 px-5 py-4">
                            <x-ui.section-header title="Ações rápidas" />
                        </div>
                        <div class="grid gap-0 divide-y divide-ink-100 md:grid-cols-2 md:divide-x md:divide-y-0">
                            @forelse ($quickActions as $action)
                                <x-dashboard.quick-action :action="$action" />
                            @empty
                                <div class="p-5">
                                    <x-ui.empty-state
                                        title="Sem ações rápidas"
                                        description="Não existem ações rápidas disponíveis para o seu perfil."
                                    />
                                </div>
                            @endforelse
                        </div>
                    </section>

                    <section class="mv-card">
                        <div class="border-b border-ink-100 px-5 py-4">
                            <x-ui.section-header title="Alertas e prazos" />
                        </div>
                        <div class="divide-y divide-ink-100">
                            @forelse (($dashboard['deadlines'] ?? []) as $alert)
                                <x-dashboard.deadline-alert :alert="$alert" />
                            @empty
                                <div class="p-5">
                                    <x-dashboard.empty-state
                                        title="Sem alertas ativos"
                                        description="Não existem prazos ou alertas autorizados para apresentar."
                                    />
                                </div>
                            @endforelse
                        </div>
                    </section>

                    <x-ui.card>
                        <div class="flex items-start gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-ink-50 text-ink-700">
                                <x-ui-icon name="alert" class="h-4 w-4" />
                            </span>
                            <div>
                                <h2 class="text-base font-semibold text-ink-900">{{ $dashboard['notifications_summary']['label'] ?? 'Notificações' }}</h2>
                                <p class="mt-1 text-sm text-ink-500">
                                    {{ $dashboard['notifications_summary']['description'] ?? 'As notificações operacionais continuam nos módulos existentes.' }}
                                </p>
                            </div>
                        </div>
                    </x-ui.card>
                </div>

                <div class="space-y-6">
                    <x-dashboard.widget-panel :widgets="$dashboard['widgets'] ?? []" />
                    <x-navigation.favorites :favorites="$favorites" />
                    <x-navigation.recent-items :items="$recentItems" />
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
