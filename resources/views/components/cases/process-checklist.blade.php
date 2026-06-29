@props([
    'items' => [],
])

<section id="case-tab-summary" class="mv-card">
    <div class="border-b border-ink-100 px-5 py-4">
        <x-ui.section-header title="Checklist processual" />
    </div>
    <div class="grid gap-3 p-5 md:grid-cols-2">
        @foreach ($items as $item)
            <div class="rounded-2xl border border-ink-100 p-4">
                <x-ui.status-badge :status="$item['status']" />
                <p class="mt-3 text-sm font-semibold text-ink-900">{{ $item['label'] }}</p>
                <p class="mt-1 text-sm text-ink-500">{{ $item['description'] }}</p>
            </div>
        @endforeach
    </div>
</section>
