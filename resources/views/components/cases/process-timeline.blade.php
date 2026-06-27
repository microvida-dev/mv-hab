@props([
    'items' => [],
])

<section id="case-tab-timeline" class="rounded-md border border-ink-100 bg-white">
    <div class="border-b border-ink-100 px-5 py-4">
        <h2 class="text-base font-semibold text-ink-900">Timeline</h2>
    </div>
    <div class="divide-y divide-ink-100">
        @forelse ($items as $item)
            <article class="px-5 py-4">
                <p class="text-xs font-semibold uppercase text-ink-500">{{ $item['date']?->format('d/m/Y H:i') ?? 'Sem data' }} · {{ $item['source'] }}</p>
                <h3 class="mt-1 text-sm font-semibold text-ink-900">{{ $item['title'] }}</h3>
                @if ($item['description'])
                    <p class="mt-1 text-sm text-ink-600">{{ $item['description'] }}</p>
                @endif
            </article>
        @empty
            <p class="px-5 py-6 text-sm text-ink-500">Ainda não existem eventos cronológicos para este processo.</p>
        @endforelse
    </div>
</section>
