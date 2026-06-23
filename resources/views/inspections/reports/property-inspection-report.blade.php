<!doctype html>
<html lang="pt">
<head><meta charset="utf-8"><title>{{ $reportNumber }}</title></head>
<body>
    <h1>Auto de vistoria {{ $reportNumber }}</h1>
    <p><strong>DOCUMENTO DEMO — SUJEITO A VALIDAÇÃO MUNICIPAL</strong></p>
    <p><strong>Vistoria:</strong> {{ $inspection->inspection_number }}</p>
    <p><strong>Tipo:</strong> {{ $inspection->inspection_type->label() }}</p>
    <p><strong>Habitação:</strong> {{ $inspection->housingUnit?->code }}</p>
    <p><strong>Data:</strong> {{ ($inspection->completed_at ?? $inspection->scheduled_for)?->format('d/m/Y H:i') }}</p>
    <p><strong>Inspetor:</strong> {{ $inspection->inspector?->name }}</p>
    <p><strong>Condição geral:</strong> {{ $inspection->general_condition?->label() }}</p>
    <h2>Itens avaliados</h2>
    <ul>@foreach ($inspection->items as $item)<li>{{ $item->label }} — {{ $item->condition?->label() ?? 'Sem avaliação' }} — {{ $item->observations }}</li>@endforeach</ul>
    <p><strong>Resumo:</strong> {{ $inspection->summary }}</p>
    <p><strong>Recomendações:</strong> {{ $inspection->recommendations }}</p>
</body>
</html>
