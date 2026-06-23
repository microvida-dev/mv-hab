<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-slate-800">{{ $housingUnit->code }}</h2>
            <a href="{{ route('backoffice.public-portal.housing-units.edit', $housingUnit) }}" class="inline-flex items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Ficha pública
            </a>
            <a href="{{ route('housing-units.edit', $housingUnit) }}" class="inline-flex items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Editar
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <x-flash-message />

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
                    <h3 class="text-lg font-semibold text-slate-900">Dados da habitação</h3>
                    <dl class="mt-6 grid gap-6 md:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Morada</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $housingUnit->address }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Estado</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $housingUnit->status->label() }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Tipologia</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $housingUnit->typology }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Quartos</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $housingUnit->bedrooms }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Renda mensal</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ number_format((float) $housingUnit->monthly_rent, 2, ',', '.') }} €</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Resumo</h3>
                    <div class="mt-4 space-y-4 text-sm text-slate-600">
                        <div class="rounded-lg bg-slate-50 p-4">
                            <p class="font-medium text-slate-900">Contratos</p>
                            <p class="mt-1">{{ $housingUnit->contracts->count() }}</p>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-4">
                            <p class="font-medium text-slate-900">Pedidos de manutenção</p>
                            <p class="mt-1">{{ $housingUnit->maintenanceRequests->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Contratos</h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($housingUnit->contracts as $contract)
                            <a href="{{ route('contracts.show', $contract) }}" class="block rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                                <p class="font-medium text-slate-900">{{ $contract->citizen->name }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $contract->status->label() }}</p>
                            </a>
                        @empty
                            <p class="text-sm text-slate-500">Sem contratos associados.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Pedidos de manutenção</h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($housingUnit->maintenanceRequests as $maintenanceRequest)
                            <a href="{{ route('maintenance-requests.show', $maintenanceRequest) }}" class="block rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                                <p class="font-medium text-slate-900">{{ $maintenanceRequest->title }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $maintenanceRequest->status->label() }}</p>
                            </a>
                        @empty
                            <p class="text-sm text-slate-500">Sem pedidos de manutenção associados.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
