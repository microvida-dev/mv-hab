@props([
    'quickActions' => [],
])

<section class="mv-card">
    <div class="border-b border-ink-100 px-5 py-4">
        <x-ui.section-header title="Ações rápidas" />
    </div>

    <div class="grid gap-0 divide-y divide-ink-100 md:grid-cols-2 md:divide-x md:divide-y-0">
        @forelse ($quickActions as $action)
            <x-dashboard.quick-action :action="$action" />
        @empty
            <div class="p-5">
                <x-ui.empty-state
                    title="Sem ações rápidas"
                    description="Não existem ações rápidas disponíveis para o seu perfil."
                />
            </div>
        @endforelse
    </div>
</section>
