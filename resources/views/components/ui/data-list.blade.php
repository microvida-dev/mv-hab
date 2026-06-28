@props([
    'items' => [],
])

<dl {{ $attributes->merge(['class' => 'grid gap-4 md:grid-cols-2']) }}>
    @foreach ($items as $item)
        <div>
            <dt class="mv-data-label">{{ $item['label'] }}</dt>
            <dd class="mv-data-value">{{ $item['value'] ?? '—' }}</dd>
            @if (($item['description'] ?? null) !== null)
                <dd class="mt-1 text-sm text-ink-500">{{ $item['description'] }}</dd>
            @endif
        </div>
    @endforeach
</dl>
