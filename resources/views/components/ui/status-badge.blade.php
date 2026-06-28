@props([
    'status' => 'neutral',
    'label' => null,
])

@php
    $normalizedStatus = str_replace('_', '-', (string) $status);
    $tone = [
        'success' => 'success',
        'completed' => 'completed',
        'done' => 'completed',
        'warning' => 'warning',
        'pending' => 'pending',
        'current' => 'civic',
        'civic' => 'civic',
        'info' => 'info',
        'danger' => 'danger',
        'overdue' => 'overdue',
        'blocked' => 'blocked',
        'cancelled' => 'neutral',
        'not-applicable' => 'neutral',
        'skipped' => 'neutral',
        'neutral' => 'neutral',
    ][$normalizedStatus] ?? 'neutral';

    $labels = [
        'completed' => 'Concluído',
        'done' => 'Concluído',
        'pending' => 'Pendente',
        'warning' => 'Atenção',
        'blocked' => 'Bloqueado',
        'overdue' => 'Vencido',
        'current' => 'Atual',
        'skipped' => 'Não aplicável',
        'not-applicable' => 'Não aplicável',
        'success' => 'Concluído',
        'danger' => 'Crítico',
        'info' => 'Informativo',
        'neutral' => 'Neutro',
    ];

    $displayLabel = $label ?? ($labels[$normalizedStatus] ?? str($status)->replace('_', ' ')->title());
@endphp

<span {{ $attributes->merge(['class' => 'mv-badge mv-badge-'.$tone]) }}>
    {{ $displayLabel }}
</span>
