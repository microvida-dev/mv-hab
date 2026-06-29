@props([
    'dashboard',
])

@php
    $teamNames = $dashboard['team_names'] ?? [];
    $metrics = $dashboard['metrics'] ?? [];
    $deadlines = $dashboard['deadlines'] ?? [];
@endphp

<section class="rounded-2xl border border-mvhab-support/30 bg-mvhab-surface/60 p-5 shadow-surface">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="text-sm font-semibold text-mvhab-primary">{{ $dashboard['greeting'] }}</p>
            <h2 class="mt-1 text-xl font-semibold text-ink-900">{{ $dashboard['profile_label'] }}</h2>
            <p class="mt-2 max-w-3xl text-sm text-ink-600">
                O painel apresenta apenas indicadores, alertas e ações compatíveis com o seu perfil, equipas e permissões atuais.
            </p>

            @if ($teamNames !== [])
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($teamNames as $teamName)
                        <x-ui.status-badge status="neutral" :label="$teamName" class="bg-white" />
                    @endforeach
                </div>
            @endif
        </div>

        <div class="rounded-2xl border border-mvhab-support/30 bg-white px-4 py-3 text-sm shadow-surface">
            <p class="font-semibold text-ink-900">{{ count($metrics) }} indicadores</p>
            <p class="mt-1 text-ink-500">{{ count($deadlines) }} alertas autorizados</p>
        </div>
    </div>
</section>
