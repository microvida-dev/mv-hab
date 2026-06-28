@props([
    'title',
    'description' => null,
    'tone' => 'neutral',
])

<x-ui.card {{ $attributes }}>
    <div class="flex items-start gap-3">
        <x-ui.status-badge :status="$tone" :label="$title" />
        <div class="min-w-0 flex-1">
            @if ($description)
                <p class="text-sm leading-5 text-ink-600">{{ $description }}</p>
            @endif

            @if (trim($slot) !== '')
                <div class="mt-3">
                    {{ $slot }}
                </div>
            @endif
        </div>
    </div>
</x-ui.card>
