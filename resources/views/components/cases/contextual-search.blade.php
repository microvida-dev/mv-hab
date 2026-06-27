@props([
    'query' => null,
    'results' => [],
])

<section class="rounded-md border border-ink-100 bg-white p-5">
    <form method="GET">
        <label for="case-search" class="text-sm font-semibold text-ink-900">Pesquisar neste processo</label>
        <div class="mt-2 flex flex-col gap-3 sm:flex-row">
            <input id="case-search" name="q" value="{{ $query }}" type="search" class="w-full rounded-md border-ink-200 text-sm shadow-sm focus:border-civic-600 focus:ring-civic-600" placeholder="Pesquisar timeline, checklist, separadores e tarefas visíveis...">
            <button class="mv-button-secondary sm:w-32">Pesquisar</button>
        </div>
    </form>

    @if ($query)
        <div class="mt-4 space-y-2">
            @forelse ($results as $result)
                <p class="rounded-md bg-ink-50 px-3 py-2 text-sm text-ink-700">{{ $result['label'] }} · {{ $result['type'] }}</p>
            @empty
                <p class="text-sm text-ink-500">Sem resultados neste contexto autorizado.</p>
            @endforelse
        </div>
    @endif
</section>
