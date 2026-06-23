<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Criar pedido de manutenção</h1></x-slot>
    <form method="POST" action="{{ route('backoffice.maintenance.requests.store') }}" enctype="multipart/form-data" class="mv-card grid gap-4">@csrf
        <select class="mv-input" name="housing_unit_id" required>@foreach ($housingUnits as $unit)<option value="{{ $unit->id }}">{{ $unit->code }} · {{ $unit->address }}</option>@endforeach</select>
        <select class="mv-input" name="maintenance_category_id"><option value="">Sem categoria</option>@foreach ($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>
        <select class="mv-input" name="urgency">@foreach ($urgencies as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
        <input class="mv-input" name="title" placeholder="Título" required><textarea class="mv-input" name="description" placeholder="Descrição" required></textarea><input class="mv-input" name="location_in_property" placeholder="Local no imóvel">
        <button class="mv-button-primary">Guardar</button>
    </form>
</x-app-layout>
