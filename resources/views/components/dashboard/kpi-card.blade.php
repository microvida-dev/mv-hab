@props([
    'metric',
])

@php
    $tone = $metric['tone'] ?? 'neutral';
    $toneClasses = [
        'civic' => 'bg-civic-50 text-civic-800',
        'warning' => 'bg-amber-50 text-amber-800',
        'danger' => 'bg-red-50 text-red-800',
        'neutral' => 'bg-ink-50 text-ink-800',
    ][$tone] ?? 'bg-ink-50 text-ink-800';
@endphp

<a href="{{ route($metric['route']) }}" class="rounded-md border border-ink-100 bg-white p-4 transition hover:border-civic-200 hover:bg-ink-50">
    <span class="inline-flex rounded-md px-2.5 py-1 text-xs font-semibold {{ $toneClasses }}">
        {{ $metric['label'] }}
    </span>
    <span class="mt-4 block text-2xl font-semibold text-ink-900">{{ $metric['value'] }}</span>
    <span class="mt-1 block text-sm leading-5 text-ink-500">{{ $metric['description'] }}</span>
</a>
