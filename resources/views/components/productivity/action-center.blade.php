@props([
    'sections' => [],
    'compact' => false,
])

<section {{ $attributes->merge(['class' => 'mv-card']) }} aria-live="polite">
    <div class="border-b border-ink-100 px-5 py-4">
        <x-ui.section-header
            title="Centro de Trabalho"
            description="Prioridades operacionais agrupadas por prazo, SLA e estado autorizado."
        />
    </div>

    <div class="{{ $compact ? 'grid gap-0 divide-y divide-ink-100 lg:grid-cols-2 lg:divide-x lg:divide-y-0' : 'divide-y divide-ink-100' }}">
        @forelse ($sections as $section)
            <div class="p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-ink-900">{{ $section['title'] }}</h3>
                        <p class="mt-1 text-xs text-ink-500">{{ $section['description'] }}</p>
                    </div>
                    <x-ui.status-badge status="neutral" :label="count($section['items'] ?? [])" />
                </div>

                <div class="mt-4 space-y-3">
                    @foreach (($section['items'] ?? []) as $item)
                        <a href="{{ $item['url'] }}" class="block rounded-2xl border border-ink-100 bg-mvhab-card p-3 transition hover:border-mvhab-support hover:bg-mvhab-surface focus:outline-none focus:ring-2 focus:ring-mvhab-primary">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-ink-900">{{ $item['title'] }}</p>
                                    <p class="mt-1 text-xs text-ink-500">{{ $item['type_label'] }} · {{ $item['suggested_action'] }}</p>
                                </div>
                                <x-productivity.deadline-indicator :deadline="$item['deadline'] ?? null" />
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="p-5">
                <x-ui.empty-state
                    title="Sem prioridades ativas"
                    description="Não existem tarefas ou pendências autorizadas para apresentar neste momento."
                />
            </div>
        @endforelse
    </div>
</section>
