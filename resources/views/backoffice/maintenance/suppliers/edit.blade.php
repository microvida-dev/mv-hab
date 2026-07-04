<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Manutenção"
            title="Editar fornecedor"
            :description="$maintenanceSupplier->name"
        />
    </x-slot>

    <form method="POST" action="{{ route('backoffice.maintenance.suppliers.update', $maintenanceSupplier) }}" class="space-y-6">
        @csrf
        @method('PATCH')

        <x-mv.section title="Dados do fornecedor">
            <div class="grid gap-4">
                <input class="mv-input" name="name" value="{{ $maintenanceSupplier->name }}" required>
                <input class="mv-input" name="email" type="email" value="{{ $maintenanceSupplier->email }}">
                <input class="mv-input" name="phone" value="{{ $maintenanceSupplier->phone }}">
                <textarea class="mv-input" name="service_scope">{{ $maintenanceSupplier->service_scope }}</textarea>
            </div>
        </x-mv.section>

        <button class="mv-button-primary">Guardar</button>
    </form>
</x-app-layout>
