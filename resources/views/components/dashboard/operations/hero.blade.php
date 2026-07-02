@props([
    'user',
])

<section class="mv-card p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-mvhab-primary">
                Centro de Operações Municipal da Habitação
            </p>

            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-ink-950">
                Painel Principal
            </h1>

            <p class="mt-2 max-w-3xl text-sm leading-6 text-ink-600">
                Aceda aos espaços de trabalho disponíveis para o seu perfil e continue a operação municipal a partir de áreas funcionais.
            </p>
        </div>

        <x-ui.action-button :href="route('public.portal')">
            <x-mv-icon name="home" size="sm" />
            <span>Portal Público</span>
        </x-ui.action-button>
    </div>
</section>
