<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Operações de inquilino"
            title="Cobranças automáticas"
            description="Consulte execuções operacionais de cobranças sem integração bancária externa ativa."
        />
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-4 px-4 py-6 sm:px-6 lg:px-8">
        <x-mv.alert>
            As cobranças automáticas registadas nesta plataforma correspondem à geração operacional de valores a cobrar e não implicam, por si só, movimento bancário externo sem integração devidamente configurada.
        </x-mv.alert>

        <x-mv.section padding="p-0" class="overflow-hidden">
            @forelse ($chargeRuns as $chargeRun)
                <a class="block border-b border-ink-100 p-4 transition hover:bg-ink-50" href="{{ route('backoffice.tenant-operations.charge-runs.show', $chargeRun) }}">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="font-semibold text-ink-900">{{ $chargeRun->run_number }}</p>
                            <p class="mt-1 text-sm text-ink-500">{{ $chargeRun->period_month }}/{{ $chargeRun->period_year }} · {{ $chargeRun->items_count }} itens</p>
                        </div>

                        <x-mv.badge>{{ $chargeRun->status?->label() }}</x-mv.badge>
                    </div>
                </a>
            @empty
                <x-mv.alert>Sem execuções registadas.</x-mv.alert>
            @endforelse
        </x-mv.section>

        {{ $chargeRuns->links() }}
    </div>
</x-app-layout>
