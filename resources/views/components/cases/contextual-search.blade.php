@props([
    'query' => null,
    'results' => [],
])

<section class="mv-card p-5">
    <form method="GET">
        <label for="case-search" class="text-sm font-semibold text-ink-900">Pesquisar neste processo</label>
        <div class="mt-2 flex flex-col gap-3 sm:flex-row">
            <input id="case-search" name="q" value="{{ $query }}" type="search" class="w-full rounded-2xl border-ink-200 text-sm shadow-surface focus:border-mvhab-primary focus:ring-mvhab-primary" placeholder="Pesquisar timeline, checklist, separadores e tarefas visíveis...">
            <x-ui.action-button type="submit" class="sm:w-32">Pesquisar</x-ui.action-button>
        </div>
    </form>

    @if ($query)
        <div class="mt-4 space-y-2">
            @forelse ($results as $result)
                <p class="rounded-2xl bg-ink-50 px-3 py-2 text-sm text-ink-700">{{ $result['label'] }} · {{ $result['type'] ?? $result['section'] ?? 'Processo' }}</p>
            @empty
                <x-ui.empty-state
                    title="Sem resultados"
                    description="Sem resultados neste contexto autorizado."
                />
            @endforelse
        </div>
    @endif
</section>
