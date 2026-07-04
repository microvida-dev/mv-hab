<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Manutenção"
            title="Editar pedido"
            :description="$maintenanceRequest->request_number ?? '#'.$maintenanceRequest->id"
        />
    </x-slot>

    <form method="POST" action="{{ route('backoffice.maintenance.requests.update', $maintenanceRequest) }}" class="space-y-6">
        @csrf
        @method('PATCH')

        <x-mv.section title="Dados do pedido">
            <div class="grid gap-4">
                <select class="mv-input" name="maintenance_category_id">
                    <option value="">Sem categoria</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected($maintenanceRequest->maintenance_category_id === $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>

                <select class="mv-input" name="urgency">
                    @foreach ($urgencies as $value => $label)
                        <option value="{{ $value }}" @selected($maintenanceRequest->urgency?->value === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <input class="mv-input" name="title" value="{{ $maintenanceRequest->title }}" required>
                <textarea class="mv-input" name="description" required>{{ $maintenanceRequest->description }}</textarea>
                <input class="mv-input" name="location_in_property" value="{{ $maintenanceRequest->location_in_property }}">
            </div>
        </x-mv.section>

        <button class="mv-button-primary">Guardar</button>
    </form>
</x-app-layout>
