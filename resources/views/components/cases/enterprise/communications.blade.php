@props([
    'communications' => [],
])

<section id="case-tab-communications" class="mv-card">
    <div class="border-b border-ink-100 px-5 py-4">
        <x-ui.section-header title="Comunicações" description="Resumo minimizado de comunicações autorizadas." />
    </div>

    <div class="divide-y divide-ink-100">
        @forelse ($communications as $communication)
            <article class="px-5 py-4">
                <p class="text-xs font-semibold uppercase text-ink-500">{{ $communication['date']?->format('d/m/Y H:i') ?? 'Sem data' }} · {{ $communication['source'] }}</p>
                <h3 class="mt-1 text-sm font-semibold text-ink-900">{{ $communication['label'] }}</h3>
                <p class="mt-1 text-sm text-ink-600">{{ $communication['description'] }}</p>
            </article>
        @empty
            <div class="p-5">
                <x-cases.enterprise.empty-state title="Sem comunicações visíveis" description="Não existem comunicações autorizadas para apresentar." />
            </div>
        @endforelse
    </div>
</section>
