@props([
    'summary' => null,
])

<x-dashboard.operations.expandable-panel
    id="notifications"
    eyebrow="Notificações"
    title="{{ $summary['label'] ?? 'Notificações operacionais' }}"
    description="{{ $summary['description'] ?? 'As notificações operacionais continuam nos módulos existentes.' }}"
    icon="notification"
    :default-open="false"
    :summary="[
        $summary['label'] ?? 'Notificações operacionais',
        'Centro global de atenção',
    ]"
>
    <div class="p-5">
        <x-dashboard.empty-state
            title="{{ $summary['label'] ?? 'Notificações operacionais' }}"
            description="{{ $summary['description'] ?? 'As notificações operacionais continuam nos módulos existentes.' }}"
        />
    </div>
</x-dashboard.operations.expandable-panel>
