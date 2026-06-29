@props([
    'result',
])

@php
    $typeLabel = str((string) $result['type'])->replace('_', ' ')->title()->toString();
@endphp

<a
    href="{{ $result['url'] }}"
    class="block rounded-2xl border border-ink-100 bg-white px-4 py-3 transition hover:border-mvhab-support hover:bg-mvhab-surface focus:outline-none focus:ring-2 focus:ring-civic-600 focus:ring-offset-2"
>
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-ink-900">{{ $result['label'] }}</p>
            @if (($result['subtitle'] ?? '') !== '')
                <p class="mt-1 text-sm text-ink-500">{{ $result['subtitle'] }}</p>
            @endif
        </div>
        <x-ui.status-badge status="neutral" :label="$typeLabel" />
    </div>
</a>
