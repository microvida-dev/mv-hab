<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-ink-900">{{ $citizen->name }}</h2>
            <a href="{{ route('citizens.edit', $citizen) }}" class="mv-button-secondary">
                Editar
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <x-flash-message />

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="mv-surface p-6 lg:col-span-2">
                    <h3 class="text-lg font-semibold text-ink-900">Dados do munícipe</h3>
                    <dl class="mt-6 grid gap-6 md:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-ink-500">Documento</dt>
                            <dd class="mt-1 text-sm text-ink-900">{{ $citizen->document_number }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-ink-500">Data de nascimento</dt>
                            <dd class="mt-1 text-sm text-ink-900">{{ $citizen->birth_date?->format('d/m/Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-ink-500">Telefone</dt>
                            <dd class="mt-1 text-sm text-ink-900">{{ $citizen->phone ?: 'Sem telefone' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-ink-500">Email</dt>
                            <dd class="mt-1 text-sm text-ink-900">{{ $citizen->email ?: 'Sem email' }}</dd>
                        </div>
                        <div class="md:col-span-2">
                            <dt class="text-sm font-medium text-ink-500">Morada</dt>
                            <dd class="mt-1 text-sm text-ink-900">{{ $citizen->address ?: 'Sem morada registada' }}</dd>
                        </div>
                        <div class="md:col-span-2">
                            <dt class="text-sm font-medium text-ink-500">Notas</dt>
                            <dd class="mt-1 text-sm text-ink-900">{{ $citizen->notes ?: 'Sem notas.' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="mv-surface p-6">
                    <h3 class="text-lg font-semibold text-ink-900">Resumo</h3>
                    <div class="mt-4 space-y-4 text-sm text-ink-600">
                        <div class="rounded-2xl bg-mvhab-surface p-4">
                            <p class="font-medium text-ink-900">Agregados</p>
                            <p class="mt-1">{{ $citizen->households->count() }}</p>
                        </div>
                        <div class="rounded-2xl bg-mvhab-surface p-4">
                            <p class="font-medium text-ink-900">Candidaturas</p>
                            <p class="mt-1">{{ $citizen->housingApplications->count() }}</p>
                        </div>
                        <div class="rounded-2xl bg-mvhab-surface p-4">
                            <p class="font-medium text-ink-900">Contratos</p>
                            <p class="mt-1">{{ $citizen->contracts->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="mv-surface p-6">
                    <h3 class="text-lg font-semibold text-ink-900">Agregados familiares</h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($citizen->households as $household)
                            <a href="{{ route('households.show', $household) }}" class="block rounded-2xl border border-ink-100 p-4 hover:bg-mvhab-surface">
                                <p class="font-medium text-ink-900">{{ $household->name }}</p>
                                <p class="mt-1 text-sm text-ink-500">{{ $household->members_count }} membros</p>
                            </a>
                        @empty
                            <p class="text-sm text-ink-500">Sem agregados associados.</p>
                        @endforelse
                    </div>
                </div>

                <div class="mv-surface p-6">
                    <h3 class="text-lg font-semibold text-ink-900">Candidaturas</h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($citizen->housingApplications as $application)
                            <a href="{{ route('applications.show', $application) }}" class="block rounded-2xl border border-ink-100 p-4 hover:bg-mvhab-surface">
                                <p class="font-medium text-ink-900">{{ $application->status->label() }}</p>
                                <p class="mt-1 text-sm text-ink-500">Pontuação: {{ $application->priority_score }}</p>
                            </a>
                        @empty
                            <p class="text-sm text-ink-500">Sem candidaturas associadas.</p>
                        @endforelse
                    </div>
                </div>

                <div class="mv-surface p-6">
                    <h3 class="text-lg font-semibold text-ink-900">Contratos</h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($citizen->contracts as $contract)
                            <a href="{{ route('contracts.show', $contract) }}" class="block rounded-2xl border border-ink-100 p-4 hover:bg-mvhab-surface">
                                <p class="font-medium text-ink-900">{{ $contract->housingUnit->code }}</p>
                                <p class="mt-1 text-sm text-ink-500">{{ $contract->status->label() }}</p>
                            </a>
                        @empty
                            <p class="text-sm text-ink-500">Sem contratos associados.</p>
                        @endforelse
                    </div>
                </div>

                <div class="mv-surface p-6">
                    <h3 class="text-lg font-semibold text-ink-900">Pedidos de manutenção</h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($citizen->maintenanceRequests as $maintenanceRequest)
                            <a href="{{ route('maintenance-requests.show', $maintenanceRequest) }}" class="block rounded-2xl border border-ink-100 p-4 hover:bg-mvhab-surface">
                                <p class="font-medium text-ink-900">{{ $maintenanceRequest->title }}</p>
                                <p class="mt-1 text-sm text-ink-500">{{ $maintenanceRequest->status->label() }}</p>
                            </a>
                        @empty
                            <p class="text-sm text-ink-500">Sem pedidos associados.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
