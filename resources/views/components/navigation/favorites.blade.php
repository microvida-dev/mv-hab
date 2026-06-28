@props([
    'favorites' => [],
])

<section class="mv-card">
    <div class="border-b border-ink-100 px-5 py-4">
        <x-ui.section-header title="Favoritos" />
    </div>
    <div class="divide-y divide-ink-100">
        @forelse ($favorites as $favorite)
            @continue(! $favorite->route_name || ! \Illuminate\Support\Facades\Route::has($favorite->route_name))

            <a href="{{ route($favorite->route_name, $favorite->route_parameters ?? []) }}" class="flex items-center gap-3 px-5 py-4 text-sm font-medium text-ink-700 transition hover:bg-ink-50 hover:text-ink-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-civic-500 focus-visible:ring-inset">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-civic-50 text-civic-700">
                    <x-ui-icon name="check" class="h-4 w-4" />
                </span>
                <span class="truncate">{{ $favorite->label }}</span>
            </a>
        @empty
            <div class="p-5">
                <x-ui.empty-state
                    title="Sem favoritos"
                    description="Fixe workspaces ou páginas usadas com frequência."
                    icon="check"
                />
            </div>
        @endforelse
    </div>
</section>
