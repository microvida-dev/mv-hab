@props([
    'action',
])

<a href="{{ route($action['route'], $action['parameters'] ?? []) }}" class="flex min-h-24 items-start gap-3 px-5 py-4 transition hover:bg-ink-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-mvhab-primary focus-visible:ring-inset">
    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl bg-mvhab-surface text-mvhab-primary">
        <x-ui-icon name="arrow" class="h-4 w-4" />
    </span>
    <span>
        <span class="block text-sm font-semibold text-ink-900">{{ $action['label'] }}</span>
        <span class="mt-1 block text-sm text-ink-500">{{ $action['description'] }}</span>
    </span>
</a>
