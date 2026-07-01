<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Comprovativos internos</h1></x-slot>
    <div class="mv-card overflow-x-auto"><table class="min-w-full text-sm"><thead><tr class="text-left text-ink-500"><th>Número</th><th>Candidato</th><th>Valor</th><th>Estado</th></tr></thead><tbody>@foreach ($receipts as $receipt)<tr class="border-t border-ink-100"><td class="py-2"><a class="text-mvhab-primary" href="{{ route('backoffice.finance.receipts.show', $receipt) }}">{{ $receipt->receipt_number }}</a></td><td>{{ $receipt->tenant?->name }}</td><td>{{ number_format((float) $receipt->total_amount, 2, ',', '.') }} EUR</td><td>{{ $receipt->status->label() }}</td></tr>@endforeach</tbody></table>{{ $receipts->links() }}</div>
</x-app-layout>
