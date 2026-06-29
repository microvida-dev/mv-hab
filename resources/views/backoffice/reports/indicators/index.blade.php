<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Indicadores</h1></x-slot>
    <div class="overflow-x-auto"><table class="mv-table"><thead><tr><th>Indicador</th><th>Categoria</th><th>Tipo</th><th>Estado</th></tr></thead><tbody>
        @foreach ($indicators as $indicator)<tr><td><a class="font-semibold text-mvhab-primary" href="{{ route('backoffice.reports.indicators.show', $indicator) }}">{{ $indicator->name }}</a><p class="text-xs text-ink-500">{{ $indicator->code }}</p></td><td>{{ $indicator->category->label() }}</td><td>{{ $indicator->value_type->label() }}</td><td>{{ $indicator->is_active ? 'Ativo' : 'Inativo' }}</td></tr>@endforeach
    </tbody></table></div><div class="mt-6">{{ $indicators->links() }}</div>
</x-app-layout>
