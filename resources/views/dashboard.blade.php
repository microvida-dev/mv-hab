<x-app-layout>
    <x-slot name="header"></x-slot>

    <div class="py-8">
        <div class="mv-page-shell">
            <x-flash-message />

            <x-dashboard.operations.hero :user="Auth::user()" />

            <x-search.universal-search :groups="$searchGroups" />

            <x-dashboard.profile-dashboard :dashboard="$dashboard" />

            @if (($productivity['enabled'] ?? false) === true)
                <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
                    <x-productivity.next-case :next-case="$productivity['next_case'] ?? null" />

                    <x-ui.card>
                        <div class="flex h-full flex-col justify-between gap-4">
                            <div>
                                <p class="mv-card-title">
                                    {{ $productivity['notification_summary']['label'] ?? 'Caixa de Entrada Municipal' }}
                                </p>
                                <p class="mv-section-description">
                                    {{ $productivity['notification_summary']['description'] ?? 'Sem notificações operacionais autorizadas.' }}
                                </p>
                            </div>

                            <x-ui.action-button :href="route('backoffice.productivity.index')">
                                <x-ui-icon name="bolt" class="h-4 w-4" />
                                <span>Abrir produtividade</span>
                            </x-ui.action-button>
                        </div>
                    </x-ui.card>
                </section>

                <x-productivity.action-center :sections="$productivity['action_center'] ?? []" compact />
            @endif

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

            <x-dashboard.operations.workspace-section :workspaces="$workspaces" :favorites="$favorites" />

            <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
                <div class="space-y-6">
                  <x-dashboard.operations.action-section :quick-actions="$quickActions" />

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
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl bg-mvhab-surface text-mvhab-primary">
                                <x-ui-icon name="alert" class="h-4 w-4" />
                            </span>

                            <div>
                                <h2 class="mv-card-title">
                                    {{ $dashboard['notifications_summary']['label'] ?? 'Notificações' }}
                                </h2>
                                <p class="mv-section-description">
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
