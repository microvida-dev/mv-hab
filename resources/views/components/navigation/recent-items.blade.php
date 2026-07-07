@props([
    'items' => [],
])

@php
    $recentItems = collect($items)
        ->filter(fn ($item) => $item->route_name && \Illuminate\Support\Facades\Route::has($item->route_name))
        ->map(fn ($item) => [
            'label' => $item->label,
            'url' => route($item->route_name, $item->route_parameters ?? []),
            'icon' => match ($item->workspace_key) {
                'atendimento' => 'contact',
                'concursos' => 'contest',
                'patrimonio' => 'housing',
                'gestao' => 'analytics',
                'administracao' => 'security',
                default => 'dashboard',
            },
            'last_visited' => $item->last_visited_at?->diffForHumans(),
            'visits_count' => (int) $item->visits_count,
            'is_frequent' => (int) $item->visits_count >= 3,
        ])
        ->values()
        ->all();
@endphp

<section class="mv-card">
    <div class="flex items-center justify-between border-b border-ink-100 px-5 py-4">
        <x-ui.section-header title="Recentes" />

        @if (count($recentItems) > 0)
            <span class="text-xs font-semibold uppercase tracking-wide text-ink-400">
                {{ count($recentItems) }}
            </span>
        @endif
    </div>

    <div class="divide-y divide-ink-100">
        @forelse ($recentItems as $item)
            <a
                href="{{ $item['url'] }}"
                class="group flex items-center gap-3 px-5 py-4 text-sm font-medium text-ink-700 transition hover:bg-ink-50 hover:text-ink-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-mvhab-primary focus-visible:ring-inset"
            >
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl bg-mvhab-surface text-mvhab-primary transition group-hover:scale-105">
                    <x-mv-icon :name="$item['icon']" size="xs" />
                </span>

                <span class="min-w-0 flex-1">
                    <span class="block truncate font-semibold text-ink-800">
                        {{ $item['label'] }}
                    </span>

                    <span class="mt-0.5 flex flex-wrap items-center gap-2 text-xs font-normal text-ink-500">
                        @if ($item['last_visited'])
                            <span>{{ $item['last_visited'] }}</span>
                        @endif

                        @if ($item['visits_count'] > 1)
                            <span class="text-ink-300">·</span>
                            <span>{{ $item['visits_count'] }} visitas</span>
                        @endif
                    </span>
                </span>

                @if ($item['is_frequent'])
                    <span class="rounded-full bg-mvhab-surface px-2.5 py-1 text-xs font-semibold text-mvhab-primary">
                        frequente
                    </span>
                @endif
            </a>
        @empty
            <div class="p-5">
                <x-ui.empty-state
                    title="Sem recentes"
                    description="Os módulos visitados aparecem aqui automaticamente."
                    icon="dashboard"
                />
            </div>
        @endforelse
    </div>
</section>
