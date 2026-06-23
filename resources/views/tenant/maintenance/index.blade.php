<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Pedidos de manutenção</h1></x-slot>
    <div class="mx-auto grid max-w-7xl gap-4 px-4 py-6 sm:px-6 lg:px-8">
        <div class="mv-card"><p class="text-sm text-ink-600">Os pedidos de manutenção serão analisados pelos serviços municipais, podendo ser solicitada informação adicional ou agendada vistoria/intervenção técnica.</p></div>
        <div><a class="mv-button-primary" href="{{ route('tenant.maintenance.create') }}">Criar pedido</a></div>
        @forelse ($maintenanceRequests as $maintenanceRequest)
            <a class="mv-card block" href="{{ route('tenant.maintenance.show', $maintenanceRequest) }}">
                <p class="font-semibold">{{ $maintenanceRequest->request_number }} · {{ $maintenanceRequest->title }}</p>
                <p class="text-sm text-ink-500">{{ $maintenanceRequest->status?->label() }} · {{ $maintenanceRequest->housingUnit?->address }}</p>
            </a>
        @empty
            <div class="mv-card"><p class="text-sm text-ink-600">Sem pedidos de manutenção.</p></div>
        @endforelse
        {{ $maintenanceRequests->links() }}
    </div>
</x-app-layout>
