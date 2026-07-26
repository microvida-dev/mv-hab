@props([
    'summary' => [],
])

@php
    $metrics = collect($summary['metrics'] ?? []);
@endphp

<section class="mv-card p-5">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-mvhab-primary">
                Resumo Operacional
            </p>

            <h2 class="mt-1 text-lg font-semibold text-ink-950">
                Indicadores do perfil
            </h2>
        </div>

        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-mvhab-surface text-mvhab-primary">
            <x-mv-icon name="dashboard" size="md" />
        </span>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @forelse($metrics as $metric)
            <x-dashboard.kpi-card :metric="$metric" />
        @empty
            <x-ui.empty-state
                title="Sem indicadores"
                description="Não existem indicadores autorizados."
            />
        @endforelse
    </div>
</section>
