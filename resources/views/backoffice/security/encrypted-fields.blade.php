<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">Campos sensíveis e encriptação</h1></x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <section class="mv-surface overflow-hidden"><table class="mv-table"><thead><tr><th>Tabela</th><th>Campo</th><th>Estado</th><th>Estratégia</th></tr></thead><tbody>@foreach ($fields as $field)<tr><td>{{ $field->table_name }}</td><td>{{ $field->field_name }}</td><td>{{ $field->encryption_status?->label() }}</td><td>{{ $field->search_strategy }}</td></tr>@endforeach</tbody></table><div class="p-4">{{ $fields->links() }}</div></section>
    </div></div>
</x-app-layout>
