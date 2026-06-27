<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-semibold text-civic-700">Workspace municipal</p>
                <h1 class="mt-1 text-2xl font-semibold leading-tight text-ink-900">{{ $workspace['title'] }}</h1>
                <p class="mt-1 max-w-3xl text-sm text-ink-500">{{ $workspace['description'] }}</p>
            </div>

            <form method="POST" action="{{ route('navigation.favorites.store') }}">
                @csrf
                <input type="hidden" name="workspace_key" value="{{ $workspace['key'] }}">
                <button type="submit" class="mv-button-secondary">
                    <x-ui-icon name="check" class="h-4 w-4" />
                    <span>Fixar workspace</span>
                </button>
            </form>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
                <div class="space-y-6">
                    @foreach ($groups as $group)
                        <section class="rounded-md border border-ink-100 bg-white">
                            <div class="border-b border-ink-100 px-5 py-4">
                                <h2 class="text-base font-semibold text-ink-900">{{ $group['label'] }}</h2>
                            </div>
                            <div class="grid divide-y divide-ink-100 md:grid-cols-2 md:divide-x md:divide-y-0">
                                @foreach ($group['items'] as $item)
                                    <a href="{{ route($item['route'], $item['parameters'] ?? []) }}" class="flex min-h-24 items-start gap-3 px-5 py-4 transition hover:bg-ink-50">
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

                    <section class="rounded-md border border-ink-100 bg-white">
                        <div class="border-b border-ink-100 px-5 py-4">
                            <h2 class="text-base font-semibold text-ink-900">Ações rápidas</h2>
                        </div>
                        <div class="divide-y divide-ink-100">
                            @forelse ($quickActions as $action)
                                <a href="{{ route($action['route'], $action['parameters'] ?? []) }}" class="flex items-center gap-3 px-5 py-4 text-sm font-medium text-ink-700 transition hover:bg-ink-50 hover:text-ink-950">
                                    <x-ui-icon name="arrow" class="h-4 w-4 text-ink-500" />
                                    <span>{{ $action['label'] }}</span>
                                </a>
                            @empty
                                <p class="px-5 py-4 text-sm text-ink-500">Sem ações rápidas disponíveis.</p>
                            @endforelse
                        </div>
                    </section>
                </aside>
            </section>
        </div>
    </div>
</x-app-layout>
