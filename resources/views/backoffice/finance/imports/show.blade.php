<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">{{ $paymentImportBatch->batch_number }}</h1></x-slot>
    <form method="POST" action="{{ route('backoffice.finance.imports.process', $paymentImportBatch) }}" class="mb-4">@csrf<button class="mv-button-secondary">Processar</button></form>
    <div class="mv-card overflow-x-auto"><table class="min-w-full text-sm"><tbody>@foreach ($paymentImportBatch->rows as $row)<tr class="border-t border-ink-100"><td class="py-2">{{ $row->row_number }}</td><td>{{ $row->reference }}</td><td>{{ $row->amount }}</td><td>{{ $row->status->label() }}</td></tr>@endforeach</tbody></table></div>
</x-app-layout>
