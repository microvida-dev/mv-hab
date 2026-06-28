@props([
    'items' => [],
])

<section class="mv-card">
    <div class="border-b border-ink-100 px-5 py-4">
        <x-ui.section-header title="Recentes" />
    </div>
    <div class="divide-y divide-ink-100">
        @forelse ($items as $item)
            @continue(! $item->route_name || ! \Illuminate\Support\Facades\Route::has($item->route_name))

            <a href="{{ route($item->route_name, $item->route_parameters ?? []) }}" class="flex items-center gap-3 px-5 py-4 text-sm font-medium text-ink-700 transition hover:bg-ink-50 hover:text-ink-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-civic-500 focus-visible:ring-inset">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-ink-50 text-ink-700">
                    <x-ui-icon name="dashboard" class="h-4 w-4" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate">{{ $item->label }}</span>
                    @if ($item->last_visited_at)
                        <span class="mt-0.5 block text-xs font-normal text-ink-500">{{ $item->last_visited_at->diffForHumans() }}</span>
                    @endif
                </span>
            </a>
        @empty
            <div class="p-5">
                <x-ui.empty-state
                    title="Sem recentes"
                    description="Os módulos visitados aparecem aqui."
                    icon="dashboard"
                />
            </div>
        @endforelse
    </div>
</section>
