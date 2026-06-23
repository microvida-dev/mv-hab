<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Editar fornecedor</h1></x-slot>
    <form method="POST" action="{{ route('backoffice.maintenance.suppliers.update', $maintenanceSupplier) }}" class="mv-card grid gap-4">@csrf @method('PATCH')
        <input class="mv-input" name="name" value="{{ $maintenanceSupplier->name }}" required><input class="mv-input" name="email" type="email" value="{{ $maintenanceSupplier->email }}"><input class="mv-input" name="phone" value="{{ $maintenanceSupplier->phone }}"><textarea class="mv-input" name="service_scope">{{ $maintenanceSupplier->service_scope }}</textarea>
        <button class="mv-button-primary">Guardar</button>
    </form>
</x-app-layout>
