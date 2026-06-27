@props([
    'items' => [],
])

@php
    $classes = [
        'completed' => 'bg-emerald-50 text-emerald-800',
        'pending' => 'bg-ink-50 text-ink-700',
        'warning' => 'bg-amber-50 text-amber-800',
        'blocked' => 'bg-red-50 text-red-800',
        'not_applicable' => 'bg-ink-50 text-ink-500',
    ];
@endphp

<section id="case-tab-summary" class="rounded-md border border-ink-100 bg-white">
    <div class="border-b border-ink-100 px-5 py-4">
        <h2 class="text-base font-semibold text-ink-900">Checklist processual</h2>
    </div>
    <div class="grid gap-3 p-5 md:grid-cols-2">
        @foreach ($items as $item)
            <div class="rounded-md border border-ink-100 p-4">
                <span class="inline-flex rounded-md px-2 py-1 text-xs font-semibold {{ $classes[$item['status']] ?? $classes['pending'] }}">
                    {{ str_replace('_', ' ', $item['status']) }}
                </span>
                <p class="mt-3 text-sm font-semibold text-ink-900">{{ $item['label'] }}</p>
                <p class="mt-1 text-sm text-ink-500">{{ $item['description'] }}</p>
            </div>
        @endforeach
    </div>
</section>
