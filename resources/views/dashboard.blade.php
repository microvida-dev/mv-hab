<x-app-layout>
    <x-slot name="header"></x-slot>

    <div class="py-8">
        <div class="mv-page-shell">
            <x-flash-message />

            {{-- Hero --}}
            <x-dashboard.operations.hero :user="Auth::user()" />

            {{-- Indicadores principais --}}
            <x-dashboard.operations.summary
                :summary="$operationsSummary"
                :productivity="$productivity"
            />

            {{-- Resumo do perfil --}}
            <x-dashboard.profile-dashboard :dashboard="$dashboard" />

            {{-- Centro operacional --}}
            <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">

                <div class="space-y-6">

                    <x-dashboard.operations.today
                        :items="$todayOperations"
                        :timeline="$operationsTimeline ?? []"
                    />

                    <x-dashboard.operations.deadlines
                        :items="$dashboard['deadlines'] ?? []"
                    />

                    <x-dashboard.operations.notifications
                        :summary="$dashboard['notifications_summary'] ?? null"
                    />

                </div>

                <x-dashboard.operations.sidebar
                    :widgets="$dashboard['widgets'] ?? []"
                    :favorites="$favorites"
                    :recent-items="$recentItems"
                    :quick-actions="$quickActions"
                    :search-groups="$searchGroups"
                />

            </section>

            {{-- Navegação avançada --}}
            <div class="mt-8">
                <x-dashboard.operations.workspace-section
                    :workspaces="$workspaces"
                    :favorites="$favorites"
                />
            </div>

        </div>
    </div>
</x-app-layout>
