@props([
    'queues' => [],
])

<section {{ $attributes->merge(['class' => 'mv-card']) }}>
    <div class="border-b border-ink-100 px-5 py-4">
        <x-ui.section-header
            title="Filas inteligentes"
            description="Filas derivadas dos estados, prioridades e prazos existentes. Não criam novos estados."
        />
    </div>

    <div class="grid gap-0 divide-y divide-ink-100 md:grid-cols-2 md:divide-x md:divide-y-0 xl:grid-cols-3">
        @forelse ($queues as $queue)
            <div class="p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-ink-900">{{ $queue['title'] }}</h3>
                        <p class="mt-1 text-xs text-ink-500">{{ $queue['criteria'] }}</p>
                    </div>
                    <x-ui.status-badge status="neutral" :label="count($queue['items'] ?? [])" />
                </div>

                <div class="mt-4 space-y-2">
                    @foreach (($queue['items'] ?? []) as $item)
                        <a href="{{ $item['url'] }}" class="block truncate rounded-2xl border border-ink-100 px-3 py-2 text-sm font-medium text-ink-800 hover:border-civic-200 hover:bg-mvhab-surface focus:outline-none focus:ring-2 focus:ring-civic-500">
                            {{ $item['title'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="p-5 md:col-span-2 xl:col-span-3">
                <x-ui.empty-state
                    title="Sem filas ativas"
                    description="Não existem itens autorizados para formar filas inteligentes."
                />
            </div>
        @endforelse
    </div>
</section>
