@props([
    'widgets' => [],
    'favorites' => [],
    'recentItems' => [],
    'quickActions' => [],
    'searchGroups' => [],
])

<aside class="space-y-6">
    <x-search.universal-search :groups="$searchGroups" />

    <x-dashboard.operations.expandable-panel
        id="sidebar-quick-actions"
        eyebrow="Produtividade"
        title="Atalhos rápidos"
        icon="arrow-right"
        :summary="[count($quickActions).' ação(ões)']"
    >
        <div class="divide-y divide-ink-100">
            @forelse ($quickActions as $action)
                <x-dashboard.quick-action :action="$action" />
            @empty
                <div class="p-5">
                    <x-dashboard.empty-state
                        title="Sem atalhos disponíveis"
                        description="Não existem ações rápidas disponíveis para o seu perfil."
                    />
                </div>
            @endforelse
        </div>
    </x-dashboard.operations.expandable-panel>

    <x-dashboard.widget-panel :widgets="$widgets" />

    <x-navigation.favorites :favorites="$favorites" />

    <x-navigation.recent-items :items="$recentItems" />
</aside>
