@props([
    'alert',
])

<a href="{{ route($alert['route']) }}" class="flex items-start gap-3 px-5 py-4 transition hover:bg-ink-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-civic-500 focus-visible:ring-inset">
    <x-ui.status-badge :status="$alert['tone'] ?? 'neutral'" :label="$alert['count']" class="mt-0.5 min-w-9 justify-center" />
    <span>
        <span class="block text-sm font-semibold text-ink-900">{{ $alert['label'] }}</span>
        <span class="mt-1 block text-sm text-ink-500">{{ $alert['description'] }}</span>
    </span>
</a>
