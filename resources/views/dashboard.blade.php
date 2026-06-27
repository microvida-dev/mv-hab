<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-semibold text-civic-700">Centro de Operações Municipal da Habitação</p>
                <h1 class="mt-1 text-2xl font-semibold leading-tight text-ink-900">Painel Principal</h1>
                <p class="mt-1 max-w-3xl text-sm text-ink-500">
                    Aceda aos workspaces disponíveis para o seu perfil e continue a operação municipal a partir de áreas funcionais.
                </p>
            </div>

            <a href="{{ route('public.portal') }}" class="mv-button-secondary">
                <x-ui-icon name="home" class="h-4 w-4" />
                <span>Portal público</span>
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <section class="rounded-md border border-ink-100 bg-white p-5">
                <label for="global-search" class="text-sm font-semibold text-ink-900">Pesquisar</label>
                <div class="mt-2 flex flex-col gap-3 md:flex-row md:items-center">
                    <input
                        id="global-search"
                        type="search"
                        class="w-full rounded-md border-ink-200 text-sm shadow-sm focus:border-civic-600 focus:ring-civic-600"
                        placeholder="Pesquisar munícipe, concurso, contrato, candidatura, documento, relatório, fogo ou Work Task..."
                        aria-describedby="global-search-help"
                    >
                    <button type="button" class="mv-button-primary md:w-40" disabled>
                        Preparado
                    </button>
                </div>
                <p id="global-search-help" class="mt-3 text-sm text-ink-500">
                    A pesquisa universal fica preparada nesta fundação e será ativada por fonte de dados nas próximas iterações.
                </p>

                @if ($searchGroups !== [])
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($searchGroups as $group)
                            <span class="rounded-md bg-ink-50 px-3 py-1 text-xs font-semibold text-ink-600">{{ $group['label'] }}</span>
                        @endforeach
                    </div>
                @endif
            </section>

            <x-dashboard.profile-dashboard :dashboard="$dashboard" />

            <section>
                <div class="mb-4 flex flex-col gap-1">
                    <h2 class="text-lg font-semibold text-ink-900">Indicadores do perfil</h2>
                    <p class="text-sm text-ink-500">Contagens agregadas e autorizadas para orientar a operação diária.</p>
                </div>

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
                <div class="mb-4 flex flex-col gap-1">
                    <h2 class="text-lg font-semibold text-ink-900">Workspaces</h2>
                    <p class="text-sm text-ink-500">Cada workspace agrupa apenas os módulos permitidos pelo seu perfil.</p>
                </div>

                <x-navigation.workspace-grid :workspaces="$workspaces" :favorites="$favorites" />
            </section>

            <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
                <div class="space-y-6">
                    <section class="rounded-md border border-ink-100 bg-white">
                        <div class="border-b border-ink-100 px-5 py-4">
                            <h2 class="text-base font-semibold text-ink-900">Ações rápidas</h2>
                        </div>
                        <div class="grid gap-0 divide-y divide-ink-100 md:grid-cols-2 md:divide-x md:divide-y-0">
                            @forelse ($quickActions as $action)
                                <x-dashboard.quick-action :action="$action" />
                            @empty
                                <p class="px-5 py-4 text-sm text-ink-500">Não existem ações rápidas disponíveis para o seu perfil.</p>
                            @endforelse
                        </div>
                    </section>

                    <section class="rounded-md border border-ink-100 bg-white">
                        <div class="border-b border-ink-100 px-5 py-4">
                            <h2 class="text-base font-semibold text-ink-900">Alertas e prazos</h2>
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

                    <section class="rounded-md border border-ink-100 bg-white p-5">
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
                    </section>
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
