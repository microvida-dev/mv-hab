<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Manutenção"
            title="Editar categoria"
            :description="$maintenanceCategory->name"
        />
    </x-slot>

    <form method="POST" action="{{ route('backoffice.maintenance.categories.update', $maintenanceCategory) }}" class="space-y-6">
        @csrf
        @method('PATCH')

        <x-mv.section title="Dados da categoria">
            <div class="grid gap-4">
                <input class="mv-input" name="code" value="{{ $maintenanceCategory->code }}" required>
                <input class="mv-input" name="name" value="{{ $maintenanceCategory->name }}" required>
                <textarea class="mv-input" name="description">{{ $maintenanceCategory->description }}</textarea>
            </div>
        </x-mv.section>

        <button class="mv-button-primary">Guardar</button>
    </form>
</x-app-layout>
