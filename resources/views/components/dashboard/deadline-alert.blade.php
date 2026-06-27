@props([
    'alert',
])

@php
    $tone = $alert['tone'] ?? 'neutral';
    $toneClasses = [
        'civic' => 'bg-civic-50 text-civic-800',
        'warning' => 'bg-amber-50 text-amber-800',
        'danger' => 'bg-red-50 text-red-800',
        'neutral' => 'bg-ink-50 text-ink-800',
    ][$tone] ?? 'bg-ink-50 text-ink-800';
@endphp

<a href="{{ route($alert['route']) }}" class="flex items-start gap-3 px-5 py-4 transition hover:bg-ink-50">
    <span class="mt-0.5 inline-flex min-w-9 justify-center rounded-md px-2 py-1 text-sm font-semibold {{ $toneClasses }}">
        {{ $alert['count'] }}
    </span>
    <span>
        <span class="block text-sm font-semibold text-ink-900">{{ $alert['label'] }}</span>
        <span class="mt-1 block text-sm text-ink-500">{{ $alert['description'] }}</span>
    </span>
</a>
