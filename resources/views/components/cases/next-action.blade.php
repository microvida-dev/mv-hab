@props([
    'action',
])

<div class="rounded-2xl border border-mvhab-support/30 bg-mvhab-surface p-4">
    <p class="text-xs font-semibold uppercase text-mvhab-primary">Próxima ação</p>
    <p class="mt-1 text-sm font-semibold text-ink-900">{{ $action['label'] }}</p>
    <p class="mt-1 text-sm text-ink-600">{{ $action['description'] }}</p>
    @if ($action['enabled'] && $action['route'])
        <a href="{{ route($action['route'], $action['parameters'] ?? []) }}" class="mt-3 inline-flex rounded-2xl text-sm font-semibold text-mvhab-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-mvhab-primary focus-visible:ring-offset-2">
            Abrir ação
        </a>
    @endif
</div>
