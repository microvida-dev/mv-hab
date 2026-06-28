@props([
    'items' => [],
])

<section id="case-tab-history" class="mv-card">
    <div class="border-b border-ink-100 px-5 py-4">
        <x-ui.section-header title="Histórico e auditoria" description="Histórico consultivo, sem payload bruto ou dados sensíveis desnecessários." />
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
            <div class="p-5">
                <x-cases.enterprise.empty-state title="Sem histórico visível" description="Não existem eventos de histórico autorizados para este caso." />
            </div>
        @endforelse
    </div>
</section>
