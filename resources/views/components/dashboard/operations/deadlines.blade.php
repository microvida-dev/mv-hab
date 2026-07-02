@props([
    'items' => [],
])

<section class="mv-card">
    <div class="border-b border-ink-100 px-5 py-4">
        <x-ui.section-header title="Alertas e prazos" />
    </div>

    <div class="divide-y divide-ink-100">
        @forelse ($items as $alert)
            <x-dashboard.deadline-alert :alert="$alert" />
        @empty
            <div class="p-5">
                <x-dashboard.empty-state
                    title="Sem alertas ativos"
                    description="Não existem prazos ou alertas autorizados para apresentar."
                />
            </div>
        @endforelse
    </div>
</section>
