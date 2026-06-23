<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">{{ $maintenanceSupplier->name }}</h1></x-slot>
    <div class="mv-card space-y-2"><p>Estado: <strong>{{ $maintenanceSupplier->status }}</strong></p><p>{{ $maintenanceSupplier->service_scope }}</p><a class="mv-button-secondary" href="{{ route('backoffice.maintenance.suppliers.edit', $maintenanceSupplier) }}">Editar</a></div>
</x-app-layout>
