@props([
    'label',
    'value',
    'description' => null,
    'currency' => false,
    'icon' => null,
])

<div class="mv-surface p-5">
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-sm font-semibold text-ink-500">{{ $label }}</p>
            <p class="mt-3 text-3xl font-semibold text-ink-900">
                @if ($currency)
                    {{ number_format((float) $value, 2, ',', '.') }} €
                @else
                    {{ number_format((int) $value, 0, ',', '.') }}
                @endif
            </p>
        </div>

        @if ($icon)
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-civic-50 text-civic-700">
                <x-ui-icon :name="$icon" class="h-5 w-5" />
            </span>
        @endif
    </div>

    @if ($description)
        <p class="mt-3 text-sm leading-5 text-ink-500">{{ $description }}</p>
    @endif
</div>
