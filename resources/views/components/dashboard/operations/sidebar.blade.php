@props([
    'widgets' => [],
    'favorites' => [],
    'recentItems' => [],
    'quickActions' => [],
    'searchGroups' => [],
    'productivity' => [],
])

@php
    $favoriteItems = collect($favorites)
        ->filter(fn ($favorite) => $favorite->route_name && \Illuminate\Support\Facades\Route::has($favorite->route_name))
        ->take(6)
        ->values();

    $recentVisibleItems = collect($recentItems)
        ->filter(fn ($item) => $item->route_name && \Illuminate\Support\Facades\Route::has($item->route_name))
        ->take(6)
        ->values();

    $quickActionItems = collect($quickActions)->take(5)->values();

    $notification = $productivity['notification_summary'] ?? null;
    $nextCase = $productivity['next_case'] ?? null;
@endphp

<aside class="space-y-5 xl:sticky xl:top-6 xl:self-start">
    <section class="mv-card p-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-mvhab-primary">
            Centro de produtividade
        </p>

        <h2 class="mt-1 text-lg font-semibold text-ink-950">
            Acesso rápido
        </h2>

        <section class="p-5">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-mvhab-surface text-mvhab-primary">
                    <x-mv-icon name="bell" size="sm" />
                </span>

                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold uppercase tracking-wide text-mvhab-primary">
                        Centro de produtividade
                    </p>

                    <h3 class="mt-1 text-sm font-semibold text-ink-950">
                        Caixa de Entrada Municipal
                    </h3>

                    @if($notification)
                        <p class="mt-1 text-xs leading-5 text-ink-500">
                            {{ $notification['description'] }}
                        </p>
                    @endif
                </div>
            </div>

            @if (($productivity['enabled'] ?? false) === true)
                <div class="mt-4">
                    <x-ui.action-button :href="route('backoffice.productivity.index')">
                        <x-mv-icon name="play" size="sm" />
                        <span>Abrir produtividade</span>
                    </x-ui.action-button>
                </div>
            @endif

            @if($nextCase)
                <div class="mt-5 rounded-3xl border border-ink-100 bg-ink-50/60 p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-ink-500">
                        Próximo processo sugerido
                    </p>

                    <p class="mt-1 text-xs leading-5 text-ink-500">
                        Sugestão operacional baseada em prioridade, SLA e prazo. Não altera dados.
                    </p>

                    <h4 class="mt-4 text-sm font-semibold text-ink-950">
                        {{ data_get($nextCase, 'title', data_get($nextCase, 'label', 'Processo sugerido')) }}
                    </h4>

                    @if(data_get($nextCase, 'reference'))
                        <p class="mt-1 text-xs font-semibold text-ink-600">
                            {{ data_get($nextCase, 'reference') }}
                        </p>
                    @endif

                    <p class="mt-2 text-xs leading-5 text-ink-500">
                        {{ data_get($nextCase, 'description', 'Sugerido por prioridade, SLA, prazo e atribuição atual.') }}
                    </p>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-bold text-ink-600 ring-1 ring-inset ring-ink-100">
                            Atribuída
                        </span>

                        <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-bold text-ink-600 ring-1 ring-inset ring-ink-100">
                            Urgente
                        </span>

                        <span class="rounded-full bg-red-50 px-2.5 py-1 text-[11px] font-bold text-red-700 ring-1 ring-inset ring-red-200">
                            Em atraso
                        </span>
                    </div>

                    @if(data_get($nextCase, 'url') || data_get($nextCase, 'href'))
                        <a
                            href="{{ data_get($nextCase, 'url', data_get($nextCase, 'href')) }}"
                            class="mt-4 inline-flex items-center gap-2 rounded-2xl bg-mvhab-primary px-4 py-2 text-xs font-bold text-white transition hover:bg-mvhab-primary-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-mvhab-primary"
                        >
                            Abrir processo
                            <span aria-hidden="true">→</span>
                        </a>
                    @endif
                </div>
            @endif
        </section>

        <form method="GET" action="{{ route('backoffice.search.index') }}" class="mt-4">
            <label for="dashboard-sidebar-search" class="sr-only">
                Pesquisar
            </label>

            <div class="flex overflow-hidden rounded-2xl border border-ink-200 bg-white shadow-surface focus-within:border-mvhab-primary focus-within:ring-4 focus-within:ring-mvhab-primary/10">
                <input
                    id="dashboard-sidebar-search"
                    name="q"
                    type="search"
                    class="min-w-0 flex-1 border-0 bg-transparent px-4 py-3 text-sm text-ink-900 placeholder:text-ink-400 focus:ring-0"
                    placeholder="Pesquisar..."
                    autocomplete="off"
                >

                <button
                    type="submit"
                    class="flex w-12 items-center justify-center text-mvhab-primary transition hover:bg-mvhab-surface"
                    aria-label="Pesquisar"
                >
                    <x-mv-icon name="search" size="sm" />
                </button>
            </div>
        </form>

        @if(count($searchGroups) > 0)
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach(collect($searchGroups)->take(3) as $group)
                    <span class="rounded-full bg-ink-50 px-2.5 py-1 text-xs font-semibold text-ink-600">
                        {{ $group['label'] }}
                    </span>
                @endforeach
            </div>
        @endif
    </section>

    <x-dashboard.operations.expandable-panel
        id="sidebar-quick-actions"
        eyebrow="Produtividade"
        title="Atalhos"
        icon="arrow-right"
        :summary="[count($quickActions).' ação(ões)']"
    >
        <div class="divide-y divide-ink-100">
            @forelse ($quickActionItems as $action)
                <a
                    href="{{ route($action['route'], $action['parameters'] ?? []) }}"
                    class="flex items-start gap-3 px-5 py-3 text-sm transition hover:bg-ink-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-mvhab-primary focus-visible:ring-inset"
                >
                    <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-2xl bg-mvhab-surface text-mvhab-primary">
                        <x-mv-icon name="arrow-right" size="xs" />
                    </span>

                    <span class="min-w-0">
                        <span class="block font-semibold text-ink-900">
                            {{ $action['label'] }}
                        </span>

                        @if(!empty($action['description']))
                            <span class="mt-0.5 block line-clamp-2 text-xs text-ink-500">
                                {{ $action['description'] }}
                            </span>
                        @endif
                    </span>
                </a>
            @empty
                <div class="p-5">
                    <x-dashboard.empty-state
                        title="Sem atalhos"
                        description="Não existem ações rápidas disponíveis."
                    />
                </div>
            @endforelse
        </div>
    </x-dashboard.operations.expandable-panel>

    <x-dashboard.operations.expandable-panel
        id="sidebar-profile-focus"
        eyebrow="Perfil"
        title="Foco"
        icon="dashboard"
        :default-open="false"
        :summary="[count($widgets).' widget(s)']"
    >
        <div class="space-y-3 p-5">
            @forelse ($widgets as $widget)
                <x-dashboard.widgets.intelligent-card :widget="$widget" />
            @empty
                <x-dashboard.empty-state
                    title="Sem foco específico"
                    description="O perfil atual não tem widgets adicionais."
                />
            @endforelse
        </div>
    </x-dashboard.operations.expandable-panel>

    <x-dashboard.operations.expandable-panel
        id="sidebar-favorites"
        eyebrow="Navegação"
        title="Favoritos"
        icon="favorite"
        :default-open="false"
        :summary="[count($favoriteItems).' favorito(s)']"
    >
        <div class="divide-y divide-ink-100">
            @forelse ($favoriteItems as $favorite)
                <a
                        href="{{ route($favorite->route_name, $favorite->route_parameters ?? []) }}"
                        class="flex items-center gap-3 px-5 py-3 text-sm font-semibold text-ink-700 transition hover:bg-ink-50 hover:text-ink-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-mvhab-primary focus-visible:ring-inset"
                >
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-2xl bg-mvhab-surface text-mvhab-primary">
                        <x-mv-icon name="favorite" size="xs" />
                    </span>

                    <span class="truncate">
                        {{ $favorite->label }}
                    </span>
                </a>
            @empty
                <div class="p-5">
                    <x-dashboard.empty-state
                        title="Sem favoritos"
                        description="Fixe módulos para acesso rápido."
                    />
                </div>
            @endforelse
        </div>
    </x-dashboard.operations.expandable-panel>

    <x-dashboard.operations.expandable-panel
        id="sidebar-recents"
        eyebrow="Histórico"
        title="Recentes"
        icon="clock"
        :default-open="false"
        :summary="[count($recentVisibleItems).' recente(s)']"
    >
        <div class="divide-y divide-ink-100">
            @forelse ($recentVisibleItems as $item)
                <a
                    href="{{ route($item->route_name, $item->route_parameters ?? []) }}"
                    class="flex items-center gap-3 px-5 py-3 text-sm text-ink-700 transition hover:bg-ink-50 hover:text-ink-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-mvhab-primary focus-visible:ring-inset"
                >
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-2xl bg-mvhab-surface text-mvhab-primary">
                        <x-mv-icon name="clock" size="xs" />
                    </span>

                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-semibold">
                            {{ $item->label }}
                        </span>

                        @if($item->last_visited_at)
                            <span class="mt-0.5 block text-xs text-ink-500">
                                {{ $item->last_visited_at->diffForHumans() }}
                            </span>
                        @endif
                    </span>
                </a>
            @empty
                <div class="p-5">
                    <x-dashboard.empty-state
                        title="Sem recentes"
                        description="Os módulos visitados aparecem aqui."
                    />
                </div>
            @endforelse
        </div>
    </x-dashboard.operations.expandable-panel>
</aside>
