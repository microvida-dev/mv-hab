<x-app-layout>
    <x-slot name="header"></x-slot>

    <div class="py-8">
        <div class="mv-page-shell">
            <x-flash-message />

            <x-dashboard.operations.hero :user="Auth::user()" />

            <x-dashboard.operations.summary
                :dashboard="$dashboard"
                :productivity="$productivity"
            />

            <x-search.universal-search :groups="$searchGroups" />

            <x-dashboard.profile-dashboard :dashboard="$dashboard" />

            @if (($productivity['enabled'] ?? false) === true)
                <x-productivity.action-center
                    :sections="$productivity['action_center'] ?? []"
                    compact
                />
            @endif

            <x-dashboard.operations.workspace-section
                :workspaces="$workspaces"
                :favorites="$favorites"
            />

            <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_26rem]">
                <div class="space-y-6">
                    <x-dashboard.operations.today :dashboard="$dashboard" />

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
                                <x-mv-icon name="bell" size="sm" />
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
