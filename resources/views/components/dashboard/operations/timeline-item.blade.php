@props([
    'item' => [],
    'showTime' => true,
])

<div class="flex items-start gap-4 py-4">
    @if($showTime)
        <div class="w-12 shrink-0 pt-1 text-xs font-semibold text-ink-500">
            {{ $item['time'] ?? '—' }}
        </div>
    @endif

    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-mvhab-surface text-mvhab-primary">
        <x-mv-icon :name="$item['icon'] ?? 'calendar'" size="sm" />
    </span>

    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
            <p class="font-semibold text-ink-900">
                {{ $item['title'] ?? $item['label'] ?? 'Prazo' }}
            </p>

            @if(!empty($item['priority']))
                <x-dashboard.operations.priority-badge :priority="$item['priority']" />
            @endif
        </div>

        @if(!empty($item['description']))
            <p class="mt-1 text-sm text-ink-600">
                {{ $item['description'] }}
            </p>
        @endif
    </div>
</div>
