<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Editar categoria</h1></x-slot>
    <form method="POST" action="{{ route('backoffice.maintenance.categories.update', $maintenanceCategory) }}" class="mv-card grid gap-4">@csrf @method('PATCH')
        <input class="mv-input" name="code" value="{{ $maintenanceCategory->code }}" required><input class="mv-input" name="name" value="{{ $maintenanceCategory->name }}" required><textarea class="mv-input" name="description">{{ $maintenanceCategory->description }}</textarea>
        <button class="mv-button-primary">Guardar</button>
    </form>
</x-app-layout>
