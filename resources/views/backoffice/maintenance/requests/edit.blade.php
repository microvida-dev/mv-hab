<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Editar pedido</h1></x-slot>
    <form method="POST" action="{{ route('backoffice.maintenance.requests.update', $maintenanceRequest) }}" class="mv-card grid gap-4">@csrf @method('PATCH')
        <select class="mv-input" name="maintenance_category_id"><option value="">Sem categoria</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected($maintenanceRequest->maintenance_category_id === $category->id)>{{ $category->name }}</option>@endforeach</select>
        <select class="mv-input" name="urgency">@foreach ($urgencies as $value => $label)<option value="{{ $value }}" @selected($maintenanceRequest->urgency?->value === $value)>{{ $label }}</option>@endforeach</select>
        <input class="mv-input" name="title" value="{{ $maintenanceRequest->title }}" required><textarea class="mv-input" name="description" required>{{ $maintenanceRequest->description }}</textarea><input class="mv-input" name="location_in_property" value="{{ $maintenanceRequest->location_in_property }}">
        <button class="mv-button-primary">Guardar</button>
    </form>
</x-app-layout>
