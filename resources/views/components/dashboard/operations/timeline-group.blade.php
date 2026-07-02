@props([
    'group' => [],
])

<div>
    <div class="mb-3 flex items-center gap-3">
        <span class="h-px flex-1 bg-ink-100"></span>
        <span class="text-xs font-semibold uppercase tracking-wide text-ink-500">
            {{ $group['label'] ?? 'Hoje' }}
        </span>
        <span class="h-px flex-1 bg-ink-100"></span>
    </div>

    <div class="divide-y divide-ink-100">
        @foreach(($group['items'] ?? []) as $item)
            <x-dashboard.operations.timeline-item :item="$item" />
        @endforeach
    </div>
</div>
