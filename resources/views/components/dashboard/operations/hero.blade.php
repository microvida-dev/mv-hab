@props([
    'user',
    'adaptiveDashboard' => [],
])

@php
    $eyebrow = data_get($adaptiveDashboard, 'eyebrow', 'Centro de Operações Municipal da Habitação');
    $headline = data_get($adaptiveDashboard, 'headline', 'Painel Principal');
    $description = data_get($adaptiveDashboard, 'description', 'Aceda aos espaços de trabalho disponíveis para o seu perfil e continue a operação municipal a partir de áreas funcionais.');
    $icon = data_get($adaptiveDashboard, 'icon', 'dashboard');
@endphp

<section class="mv-card p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-4">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-mvhab-surface text-mvhab-primary">
                <x-mv-icon :name="$icon" size="lg" />
            </span>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-mvhab-primary">
                    {{ $eyebrow }}
                </p>

                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-ink-950">
                    {{ $headline }}
                </h1>

                <p class="mt-2 max-w-3xl text-sm leading-6 text-ink-600">
                    {{ $description }}
                </p>
            </div>
        </div>

        <x-ui.action-button :href="route('public.portal')">
            <x-mv-icon name="home" size="sm" />
            <span>Portal Público</span>
        </x-ui.action-button>
    </div>
</section>
