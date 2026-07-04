<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Manutenção"
            title="Criar pedido de manutenção"
            description="Registe um pedido manual para acompanhamento interno."
        />
    </x-slot>

    <form method="POST" action="{{ route('backoffice.maintenance.requests.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <x-mv.section title="Dados do pedido">
            <div class="grid gap-4">
                <select class="mv-input" name="housing_unit_id" required>
                    @foreach ($housingUnits as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->code }} · {{ $unit->address }}</option>
                    @endforeach
                </select>

                <select class="mv-input" name="maintenance_category_id">
                    <option value="">Sem categoria</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>

                <select class="mv-input" name="urgency">
                    @foreach ($urgencies as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>

                <input class="mv-input" name="title" placeholder="Título" required>
                <textarea class="mv-input" name="description" placeholder="Descrição" required></textarea>
                <input class="mv-input" name="location_in_property" placeholder="Local no imóvel">
            </div>
        </x-mv.section>

        <button class="mv-button-primary">Guardar</button>
    </form>
</x-app-layout>
