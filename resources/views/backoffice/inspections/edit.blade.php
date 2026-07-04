<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Vistorias"
            title="Editar vistoria"
            :description="$propertyInspection->inspection_number"
        />
    </x-slot>

    <form method="POST" action="{{ route('backoffice.inspections.update', $propertyInspection) }}" class="space-y-6">
        @csrf
        @method('PATCH')

        <x-mv.section title="Dados da vistoria">
            <div class="grid gap-4">
                <select class="mv-input" name="housing_unit_id" required>
                    @foreach ($housingUnits as $unit)
                        <option value="{{ $unit->id }}" @selected($propertyInspection->housing_unit_id === $unit->id)>{{ $unit->code }} · {{ $unit->address }}</option>
                    @endforeach
                </select>

                <select class="mv-input" name="inspection_type">
                    <option value="initial" @selected($propertyInspection->inspection_type->value === 'initial')>Inicial</option>
                    <option value="periodic" @selected($propertyInspection->inspection_type->value === 'periodic')>Periódica</option>
                    <option value="final" @selected($propertyInspection->inspection_type->value === 'final')>Final</option>
                    <option value="extraordinary" @selected($propertyInspection->inspection_type->value === 'extraordinary')>Extraordinária</option>
                </select>

                <textarea class="mv-input" name="summary">{{ $propertyInspection->summary }}</textarea>
            </div>
        </x-mv.section>

        <button class="mv-button-primary">Guardar</button>
    </form>
</x-app-layout>
