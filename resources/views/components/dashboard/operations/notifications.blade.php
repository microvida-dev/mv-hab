@props([
    'summary' => null,
])

<x-ui.card>
    <div class="flex items-start gap-3">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl bg-mvhab-surface text-mvhab-primary">
            <x-mv-icon name="bell" size="sm" />
        </span>

        <div>
            <h2 class="mv-card-title">
                {{ $summary['label'] ?? 'Notificações' }}
            </h2>
            <p class="mv-section-description">
                {{ $summary['description'] ?? 'As notificações operacionais continuam nos módulos existentes.' }}
            </p>
        </div>
    </div>
</x-ui.card>
