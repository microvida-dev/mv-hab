@props([
    'favorites' => [],
])

<section class="mv-card">
    <div class="flex items-center justify-between border-b border-ink-100 px-5 py-4">
        <x-ui.section-header title="Favoritos" />

        @if (count($favorites) > 0)
            <span class="text-xs font-semibold uppercase tracking-wide text-ink-400">
                {{ count($favorites) }}
            </span>
        @endif
    </div>

    <div class="divide-y divide-ink-100">
        @forelse ($favorites as $favorite)
            @continue(! $favorite->route_name || ! \Illuminate\Support\Facades\Route::has($favorite->route_name))

            <div class="group flex items-center gap-3 px-5 py-4 transition hover:bg-ink-50">
                <a
                    href="{{ route($favorite->route_name, $favorite->route_parameters ?? []) }}"
                    class="flex min-w-0 flex-1 items-center gap-3 text-sm font-medium text-ink-700 transition hover:text-ink-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-mvhab-primary focus-visible:ring-inset"
                >
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-2xl bg-mvhab-surface text-mvhab-primary">
                        <x-mv-icon name="check" size="xs" />
                    </span>

                    <span class="truncate">{{ $favorite->label }}</span>
                </a>

                <form method="POST" action="{{ route('navigation.favorites.destroy', $favorite) }}">
                    @csrf
                    @method('DELETE')

                    <button
                        type="submit"
                        class="rounded-xl px-2 py-1 text-xs font-semibold text-ink-400 opacity-100 transition hover:bg-red-50 hover:text-red-700 md:opacity-0 md:group-hover:opacity-100"
                        onclick="return confirm('Remover este favorito?')"
                        aria-label="Remover favorito {{ $favorite->label }}"
                    >
                        Remover
                    </button>
                </form>
            </div>
        @empty
            <div class="p-5">
                <x-ui.empty-state
                    title="Sem favoritos"
                    description="Fixe os espaços de trabalho usados com frequência para acesso rápido."
                    icon="check"
                />
            </div>
        @endforelse
    </div>
</section>
