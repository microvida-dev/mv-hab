@props([
    'title',
    'primaryLabel',
    'primaryValue',
    'secondaryLabel',
    'secondaryValue',
])

<x-ui.card {{ $attributes->merge(['class' => 'space-y-4']) }}>
    <h3 class="font-semibold text-ink-900">{{ $title }}</h3>
    <div class="grid gap-3 sm:grid-cols-2">
        <div class="rounded-2xl bg-ink-50 p-4">
            <p class="text-sm text-ink-500">{{ $primaryLabel }}</p>
            <p class="mt-2 text-2xl font-semibold text-ink-900">{{ $primaryValue }}</p>
        </div>
        <div class="rounded-2xl bg-ink-50 p-4">
            <p class="text-sm text-ink-500">{{ $secondaryLabel }}</p>
            <p class="mt-2 text-2xl font-semibold text-ink-900">{{ $secondaryValue }}</p>
        </div>
    </div>
</x-ui.card>
