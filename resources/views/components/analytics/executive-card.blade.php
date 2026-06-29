@props([
    'summary',
])

<x-ui.card {{ $attributes->merge(['class' => 'space-y-4']) }}>
    <div>
        <p class="text-sm font-semibold text-mvhab-primary">Resumo executivo</p>
        <h2 class="mt-1 text-xl font-semibold text-ink-900">{{ $summary['title'] ?? 'Leitura executiva municipal' }}</h2>
        <p class="mt-2 text-sm leading-6 text-ink-600">{{ $summary['description'] ?? '' }}</p>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <p class="text-sm font-semibold text-ink-900">Destaques</p>
            <ul class="mt-2 space-y-2 text-sm text-ink-600">
                @forelse (($summary['highlights'] ?? []) as $highlight)
                    <li class="flex gap-2"><span aria-hidden="true" class="mt-2 h-1.5 w-1.5 rounded-full bg-success-500"></span><span>{{ $highlight }}</span></li>
                @empty
                    <li>Sem destaques para os filtros selecionados.</li>
                @endforelse
            </ul>
        </div>
        <div>
            <p class="text-sm font-semibold text-ink-900">Alertas</p>
            <ul class="mt-2 space-y-2 text-sm text-ink-600">
                @forelse (($summary['warnings'] ?? []) as $warning)
                    <li class="flex gap-2"><span aria-hidden="true" class="mt-2 h-1.5 w-1.5 rounded-full bg-warning-500"></span><span>{{ $warning }}</span></li>
                @empty
                    <li>Sem alertas agregados relevantes.</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-ui.card>
