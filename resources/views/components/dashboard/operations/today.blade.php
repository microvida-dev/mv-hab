@props([
    'items' => [],
])

<section class="mv-card p-5">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-mvhab-primary">
                Operação
            </p>

            <h2 class="mt-1 text-lg font-semibold text-ink-950">
                Hoje
            </h2>
        </div>

        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-mvhab-surface text-mvhab-primary">
            <x-mv-icon name="calendar" size="md" />
        </span>
    </div>

    <div class="mt-6 divide-y divide-ink-100">

        @forelse($items as $alert)

            <div class="flex items-start gap-4 py-4">

                <span class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-xl bg-mvhab-surface text-mvhab-primary">
                    <x-mv-icon name="calendar" size="sm"/>
                </span>

                <div class="min-w-0 flex-1">

                    <p class="font-semibold text-ink-900">
                        {{ $alert['title'] ?? 'Prazo' }}
                    </p>

                    @if(!empty($alert['description']))
                        <p class="mt-1 text-sm text-ink-600">
                            {{ $alert['description'] }}
                        </p>
                    @endif

                </div>

            </div>

        @empty

            <x-ui.empty-state
                title="Sem atividades para hoje"
                description="Não existem tarefas ou prazos autorizados."
            />

        @endforelse

    </div>

</section>
