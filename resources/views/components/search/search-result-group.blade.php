@props([
    'group',
])

<section class="space-y-3">
    <div class="flex items-center justify-between gap-3">
        <h2 class="text-base font-semibold text-ink-900">{{ $group['label'] }}</h2>
        <span class="text-xs font-medium uppercase tracking-wide text-ink-400">
            {{ count($group['results'] ?? []) }} resultado(s)
        </span>
    </div>

    <div class="space-y-2">
        @foreach (($group['results'] ?? []) as $result)
            <x-search.search-result-item :result="$result" />
        @endforeach
    </div>
</section>
