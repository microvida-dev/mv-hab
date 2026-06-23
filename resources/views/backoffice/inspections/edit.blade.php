<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Editar vistoria</h1></x-slot>
    <form method="POST" action="{{ route('backoffice.inspections.update', $propertyInspection) }}" class="mv-card grid gap-4">@csrf @method('PATCH')
        <select class="mv-input" name="housing_unit_id" required>@foreach ($housingUnits as $unit)<option value="{{ $unit->id }}" @selected($propertyInspection->housing_unit_id === $unit->id)>{{ $unit->code }} · {{ $unit->address }}</option>@endforeach</select>
        <select class="mv-input" name="inspection_type"><option value="initial" @selected($propertyInspection->inspection_type->value === 'initial')>Inicial</option><option value="periodic" @selected($propertyInspection->inspection_type->value === 'periodic')>Periódica</option><option value="final" @selected($propertyInspection->inspection_type->value === 'final')>Final</option><option value="extraordinary" @selected($propertyInspection->inspection_type->value === 'extraordinary')>Extraordinária</option></select>
        <textarea class="mv-input" name="summary">{{ $propertyInspection->summary }}</textarea><button class="mv-button-primary">Guardar</button>
    </form>
</x-app-layout>
