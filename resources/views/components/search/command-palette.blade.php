@props([
    'commands',
])

<x-ui.card>
    <div class="flex items-start gap-3">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-civic-50 text-civic-700">
            <x-ui-icon name="bolt" class="h-5 w-5" />
        </span>
        <div>
            <h2 class="text-base font-semibold text-ink-900">Centro de Comandos</h2>
            <p class="mt-1 text-sm text-ink-500">Atalhos seguros para páginas e ações não destrutivas autorizadas.</p>
        </div>
    </div>

    <div class="mt-5 space-y-5">
        @forelse (($commands['groups'] ?? []) as $group)
            <x-search.search-result-group :group="$group" />
        @empty
            <x-search.empty-results :term="$commands['term'] ?? ''" />
        @endforelse
    </div>
</x-ui.card>
