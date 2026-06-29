@props([
    'summary',
])

<section class="mv-card p-5">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="text-sm font-semibold text-mvhab-primary">Espaço de Trabalho do Processo</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $summary['title'] }} {{ $summary['reference'] }}</h1>
            <p class="mt-2 max-w-3xl text-sm text-ink-500">{{ $summary['description'] }}</p>
            <div class="mt-3 flex flex-wrap gap-2">
                @if ($summary['program'])
                    <x-ui.status-badge status="neutral" :label="$summary['program']" />
                @endif
                <x-ui.status-badge status="civic" :label="$summary['status']" />
                <x-ui.status-badge status="neutral" :label="'Prioridade '.$summary['priority']" />
            </div>
        </div>

        <div class="rounded-2xl border border-ink-100 bg-ink-50 px-4 py-3 text-sm">
            <p class="font-semibold text-ink-900">Responsável</p>
            <p class="mt-1 text-ink-600">{{ $summary['responsible'] }}</p>
            <p class="mt-2 text-xs font-semibold uppercase text-ink-500">{{ $summary['team'] }}</p>
        </div>
    </div>
</section>
