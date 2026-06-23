<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Vistorias</h1></x-slot>
    <a class="mv-button-primary" href="{{ route('backoffice.inspections.create') }}">Criar vistoria</a>
    <div class="mv-card mt-4 overflow-x-auto"><table class="min-w-full text-sm"><thead><tr class="text-left text-ink-500"><th>Número</th><th>Tipo</th><th>Habitação</th><th>Estado</th><th>Data</th></tr></thead><tbody>@foreach ($inspections as $inspection)<tr class="border-t border-ink-100"><td class="py-2"><a class="text-civic-700" href="{{ route('backoffice.inspections.show', $inspection) }}">{{ $inspection->inspection_number }}</a></td><td>{{ $inspection->inspection_type->label() }}</td><td>{{ $inspection->housingUnit?->code }}</td><td>{{ $inspection->status->label() }}</td><td>{{ $inspection->scheduled_for?->format('d/m/Y H:i') }}</td></tr>@endforeach</tbody></table>{{ $inspections->links() }}</div>
</x-app-layout>
