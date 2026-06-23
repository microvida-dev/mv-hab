<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Criar pedido de manutenção</h1></x-slot>
    <form method="POST" action="{{ route('candidate.maintenance.requests.store') }}" enctype="multipart/form-data" class="mv-card grid gap-4">@csrf
        <p class="text-sm text-ink-600">Descreva o problema com o máximo de detalhe possível. Pode anexar fotografias para ajudar os serviços municipais a avaliar a situação.</p>
        <select class="mv-input" name="maintenance_category_id"><option value="">Categoria</option>@foreach ($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>
        <select class="mv-input" name="urgency">@foreach ($urgencies as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
        <input class="mv-input" name="title" placeholder="Título" required><textarea class="mv-input" name="description" placeholder="Descrição" required></textarea><input class="mv-input" name="location_in_property" placeholder="Localização no imóvel"><textarea class="mv-input" name="tenant_availability" placeholder="Disponibilidade para contacto/intervenção"></textarea><input class="mv-input" type="file" name="attachments[]" multiple>
        <button class="mv-button-primary">Submeter pedido</button>
    </form>
</x-app-layout>
