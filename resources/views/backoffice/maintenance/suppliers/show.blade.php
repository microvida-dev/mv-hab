<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Fornecedor"
            :title="$maintenanceSupplier->name"
            :description="$maintenanceSupplier->status"
        >
            <x-slot name="actions">
                <a class="mv-button-secondary" href="{{ route('backoffice.maintenance.suppliers.edit', $maintenanceSupplier) }}">Editar</a>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <x-mv.section title="Âmbito de serviço">
        <x-mv.badge>{{ $maintenanceSupplier->status }}</x-mv.badge>
        <p class="mt-4 text-sm leading-6 text-ink-700">{{ $maintenanceSupplier->service_scope }}</p>
    </x-mv.section>
</x-app-layout>
