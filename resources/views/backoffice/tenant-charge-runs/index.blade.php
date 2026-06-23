<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Cobranças automáticas</h1></x-slot>
    <div class="mx-auto grid max-w-7xl gap-4 px-4 py-6 sm:px-6 lg:px-8">
        <div class="mv-card"><p class="text-sm text-ink-600">As cobranças automáticas registadas nesta plataforma correspondem à geração operacional de valores a cobrar e não implicam, por si só, movimento bancário externo sem integração devidamente configurada.</p></div>
        @forelse ($chargeRuns as $chargeRun)
            <a class="mv-card block" href="{{ route('backoffice.tenant-operations.charge-runs.show', $chargeRun) }}">
                <p class="font-semibold">{{ $chargeRun->run_number }}</p>
                <p class="text-sm text-ink-500">{{ $chargeRun->period_month }}/{{ $chargeRun->period_year }} · {{ $chargeRun->status?->label() }} · {{ $chargeRun->items_count }} itens</p>
            </a>
        @empty
            <div class="mv-card"><p class="text-sm text-ink-600">Sem execuções registadas.</p></div>
        @endforelse
        {{ $chargeRuns->links() }}
    </div>
</x-app-layout>
