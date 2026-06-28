@props([
    'action',
])

<div class="rounded-lg border border-civic-100 bg-civic-50 p-4">
    <p class="text-xs font-semibold uppercase text-civic-700">Próxima ação</p>
    <p class="mt-1 text-sm font-semibold text-ink-900">{{ $action['label'] }}</p>
    <p class="mt-1 text-sm text-ink-600">{{ $action['description'] }}</p>
    @if ($action['enabled'] && $action['route'])
        <a href="{{ route($action['route']) }}" class="mt-3 inline-flex rounded-md text-sm font-semibold text-civic-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-civic-500 focus-visible:ring-offset-2">
            Abrir ação
        </a>
    @endif
</div>
