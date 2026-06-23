<x-app-layout>
    <x-slot name="header"><div><h1 class="text-xl font-semibold text-ink-900">{{ $run->definition->name }}</h1><p class="mt-1 text-sm text-ink-500">Execução {{ $run->public_id }}</p></div></x-slot>
    <div class="space-y-6">
        <div class="flex flex-wrap gap-5 text-sm text-ink-600"><span>Estado: <strong>{{ $run->status->label() }}</strong></span><span>Linhas: <strong>{{ $run->row_count }}</strong></span><span>Âmbito: <strong>{{ $run->scope->label() }}</strong></span></div>
        <div class="overflow-x-auto"><table class="mv-table">@if($rows !== [])<thead><tr>@foreach(array_keys($rows[0]) as $header)<th>{{ $header }}</th>@endforeach</tr></thead>@endif<tbody>@forelse($rows as $row)<tr>@foreach($row as $value)<td>{{ is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value }}</td>@endforeach</tr>@empty<tr><td>Sem resultados para os filtros aplicados.</td></tr>@endforelse</tbody></table></div>
    </div>
</x-app-layout>
