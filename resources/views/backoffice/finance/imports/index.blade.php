<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Importação de pagamentos</h1></x-slot>
    <a class="mv-button-primary" href="{{ route('backoffice.finance.imports.create') }}">Importar CSV</a>
    <div class="mv-card mt-4 overflow-x-auto"><table class="min-w-full text-sm"><tbody>@foreach ($batches as $batch)<tr class="border-t border-ink-100"><td class="py-2"><a class="text-mvhab-primary" href="{{ route('backoffice.finance.imports.show', $batch) }}">{{ $batch->batch_number }}</a></td><td>{{ $batch->status->label() }}</td><td>{{ $batch->row_count }} linhas</td></tr>@endforeach</tbody></table>{{ $batches->links() }}</div>
</x-app-layout>
