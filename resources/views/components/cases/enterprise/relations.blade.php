@props([
    'relations' => [],
])

<section id="case-tab-relations" class="mv-card">
    <div class="border-b border-ink-100 px-5 py-4">
        <x-ui.section-header title="Relações entre casos" description="Apenas relações autorizadas são apresentadas." />
    </div>

    <div class="grid gap-3 p-5 md:grid-cols-2">
        @forelse ($relations as $relation)
            <div class="rounded-2xl border border-ink-100 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-ink-900">{{ $relation['label'] }}</p>
                        <p class="mt-1 text-sm text-ink-500">{{ $relation['description'] }}</p>
                    </div>
                    <x-ui.status-badge :status="$relation['status']" :label="$relation['type']" />
                </div>

                @if ($relation['route'])
                    <a href="{{ route($relation['route'], $relation['parameters'] ?? []) }}" class="mt-3 inline-flex text-sm font-semibold text-mvhab-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-mvhab-primary focus-visible:ring-offset-2">
                        Abrir relação
                    </a>
                @endif
            </div>
        @empty
            <x-cases.enterprise.empty-state title="Sem relações visíveis" description="Não existem relações autorizadas para apresentar." />
        @endforelse
    </div>
</section>
