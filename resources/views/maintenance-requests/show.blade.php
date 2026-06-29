<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-ink-900">{{ $maintenanceRequest->title }}</h2>
            <a href="{{ route('maintenance-requests.edit', $maintenanceRequest) }}" class="mv-button-secondary">
                Editar
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
            <x-flash-message />

            <div class="mv-surface p-6">
                <h3 class="text-lg font-semibold text-ink-900">Dados do pedido</h3>
                <dl class="mt-6 grid gap-6 md:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-ink-500">Habitação</dt>
                        <dd class="mt-1 text-sm text-ink-900">{{ $maintenanceRequest->housingUnit->code }} - {{ $maintenanceRequest->housingUnit->address }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-ink-500">Munícipe</dt>
                        <dd class="mt-1 text-sm text-ink-900">{{ $maintenanceRequest->citizen?->name ?: 'Sem munícipe associado' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-ink-500">Prioridade</dt>
                        <dd class="mt-1 text-sm text-ink-900">{{ $maintenanceRequest->priority->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-ink-500">Estado</dt>
                        <dd class="mt-1 text-sm text-ink-900">{{ $maintenanceRequest->status->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-ink-500">Reportado em</dt>
                        <dd class="mt-1 text-sm text-ink-900">{{ $maintenanceRequest->reported_at?->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-ink-500">Resolvido em</dt>
                        <dd class="mt-1 text-sm text-ink-900">{{ $maintenanceRequest->resolved_at?->format('d/m/Y H:i') ?: 'Ainda não resolvido' }}</dd>
                    </div>
                    <div class="md:col-span-2">
                        <dt class="text-sm font-medium text-ink-500">Descrição</dt>
                        <dd class="mt-1 text-sm text-ink-900">{{ $maintenanceRequest->description }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>
