<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            eyebrow="Workspace municipal"
            :title="$workspace['title']"
            :description="$workspace['description']"
        >
            <x-slot name="actions">
                <form method="POST" action="{{ route('navigation.favorites.store') }}">
                    @csrf
                    <input type="hidden" name="workspace_key" value="{{ $workspace['key'] }}">
                    <x-ui.action-button type="submit">
                        <x-ui-icon name="check" class="h-4 w-4" />
                        <span>Fixar workspace</span>
                    </x-ui.action-button>
                </form>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="py-8">
        <div class="mv-page-shell">
            <x-flash-message />

            <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
                <div class="space-y-6">
                    @foreach ($groups as $group)
                        <section class="mv-card">
                            <div class="border-b border-ink-100 px-5 py-4">
                                <x-ui.section-header :title="$group['label']" />
                            </div>
                            <div class="grid divide-y divide-ink-100 md:grid-cols-2 md:divide-x md:divide-y-0">
                                @foreach ($group['items'] as $item)
                                    <a href="{{ route($item['route'], $item['parameters'] ?? []) }}" class="flex min-h-24 items-start gap-3 px-5 py-4 transition hover:bg-ink-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-civic-500 focus-visible:ring-inset">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-civic-50 text-civic-700">
                                            <x-ui-icon :name="$item['icon'] ?? 'dashboard'" class="h-4 w-4" />
                                        </span>
                                        <span>
                                            <span class="block text-sm font-semibold text-ink-900">{{ $item['label'] }}</span>
                                            <span class="mt-1 block text-sm text-ink-500">Abrir módulo no contexto deste workspace.</span>
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>

                <aside class="space-y-6">
                    <x-navigation.favorites :favorites="$favorites" />
                    <x-navigation.recent-items :items="$recentItems" />

                    <section class="mv-card">
                        <div class="border-b border-ink-100 px-5 py-4">
                            <x-ui.section-header title="Ações rápidas" />
                        </div>
                        <div class="divide-y divide-ink-100">
                            @forelse ($quickActions as $action)
                                <a href="{{ route($action['route'], $action['parameters'] ?? []) }}" class="flex items-center gap-3 px-5 py-4 text-sm font-medium text-ink-700 transition hover:bg-ink-50 hover:text-ink-950">
                                    <x-ui-icon name="arrow" class="h-4 w-4 text-ink-500" />
                                    <span>{{ $action['label'] }}</span>
                                </a>
                            @empty
                                <div class="p-5">
                                    <x-ui.empty-state
                                        title="Sem ações rápidas"
                                        description="Sem ações rápidas disponíveis."
                                    />
                                </div>
                            @endforelse
                        </div>
                    </section>
                </aside>
            </section>
        </div>
    </div>
</x-app-layout>
