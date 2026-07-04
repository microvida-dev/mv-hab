<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Área do Inquilino"
            title="Pedidos de manutenção"
            description="Acompanhe pedidos enviados aos serviços municipais."
        >
            <x-slot name="actions">
                <a class="mv-button-primary" href="{{ route('tenant.maintenance.create') }}">Criar pedido</a>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-4 px-4 py-6 sm:px-6 lg:px-8">
        <x-mv.alert>
            Os pedidos de manutenção serão analisados pelos serviços municipais, podendo ser solicitada informação adicional ou agendada vistoria/intervenção técnica.
        </x-mv.alert>

        <x-mv.section padding="p-0" class="overflow-hidden">
            @forelse ($maintenanceRequests as $maintenanceRequest)
                <a class="block border-b border-ink-100 p-4 transition hover:bg-ink-50" href="{{ route('tenant.maintenance.show', $maintenanceRequest) }}">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="font-semibold text-ink-900">{{ $maintenanceRequest->request_number }} · {{ $maintenanceRequest->title }}</p>
                            <p class="mt-1 text-sm text-ink-500">{{ $maintenanceRequest->housingUnit?->address }}</p>
                        </div>

                        <x-mv.badge>{{ $maintenanceRequest->status?->label() }}</x-mv.badge>
                    </div>
                </a>
            @empty
                <x-mv.alert>Sem pedidos de manutenção.</x-mv.alert>
            @endforelse
        </x-mv.section>

        {{ $maintenanceRequests->links() }}
    </div>
</x-app-layout>
