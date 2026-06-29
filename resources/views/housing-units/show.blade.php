<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-ink-900">
                {{ $housingUnit->code }}
            </h2>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('backoffice.public-portal.housing-units.edit', $housingUnit) }}" class="mv-button-secondary">
                    Ficha pública
                </a>

                <a href="{{ route('housing-units.edit', $housingUnit) }}" class="mv-button-primary">
                    Editar
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <div class="grid gap-6 lg:grid-cols-3">
                <section class="mv-surface p-6 lg:col-span-2">
                    <h3 class="text-lg font-semibold text-ink-900">
                        Dados da habitação
                    </h3>

                    <dl class="mt-6 grid gap-6 md:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-ink-500">Morada</dt>
                            <dd class="mt-1 text-sm text-ink-900">{{ $housingUnit->address }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-ink-500">Estado</dt>
                            <dd class="mt-1 text-sm text-ink-900">{{ $housingUnit->status->label() }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-ink-500">Tipologia</dt>
                            <dd class="mt-1 text-sm text-ink-900">{{ $housingUnit->typology }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-ink-500">Quartos</dt>
                            <dd class="mt-1 text-sm text-ink-900">{{ $housingUnit->bedrooms }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-ink-500">Renda mensal</dt>
                            <dd class="mt-1 text-sm text-ink-900">{{ number_format((float) $housingUnit->monthly_rent, 2, ',', '.') }} €</dd>
                        </div>
                    </dl>
                </section>

                <section class="mv-surface p-6">
                    <h3 class="text-lg font-semibold text-ink-900">
                        Resumo
                    </h3>

                    <div class="mt-4 space-y-4 text-sm text-ink-600">
                        <div class="rounded-2xl bg-mvhab-surface p-4">
                            <p class="font-medium text-ink-900">Contratos</p>
                            <p class="mt-1">{{ $housingUnit->contracts->count() }}</p>
                        </div>

                        <div class="rounded-2xl bg-mvhab-surface p-4">
                            <p class="font-medium text-ink-900">Pedidos de manutenção</p>
                            <p class="mt-1">{{ $housingUnit->maintenanceRequests->count() }}</p>
                        </div>
                    </div>
                </section>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="mv-surface p-6">
                    <h3 class="text-lg font-semibold text-ink-900">
                        Contratos
                    </h3>

                    <div class="mt-4 space-y-3">
                        @forelse ($housingUnit->contracts as $contract)
                            <a
                                href="{{ route('contracts.show', $contract) }}"
                                class="block rounded-2xl border border-ink-100 p-4 transition hover:bg-mvhab-surface"
                            >
                                <p class="font-medium text-ink-900">
                                    {{ $contract->citizen->name }}
                                </p>

                                <p class="mt-1 text-sm text-ink-500">
                                    {{ $contract->status->label() }}
                                </p>
                            </a>
                        @empty
                            <p class="text-sm text-ink-500">
                                Sem contratos associados.
                            </p>
                        @endforelse
                    </div>
                </section>

                <section class="mv-surface p-6">
                    <h3 class="text-lg font-semibold text-ink-900">
                        Pedidos de manutenção
                    </h3>

                    <div class="mt-4 space-y-3">
                        @forelse ($housingUnit->maintenanceRequests as $maintenanceRequest)
                            <a
                                href="{{ route('maintenance-requests.show', $maintenanceRequest) }}"
                                class="block rounded-2xl border border-ink-100 p-4 transition hover:bg-mvhab-surface"
                            >
                                <p class="font-medium text-ink-900">
                                    {{ $maintenanceRequest->title }}
                                </p>

                                <p class="mt-1 text-sm text-ink-500">
                                    {{ $maintenanceRequest->status->label() }}
                                </p>
                            </a>
                        @empty
                            <p class="text-sm text-ink-500">
                                Sem pedidos de manutenção associados.
                            </p>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
