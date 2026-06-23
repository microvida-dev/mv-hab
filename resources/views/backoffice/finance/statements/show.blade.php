<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Extrato {{ $tenantFinancialAccount->account_number }}</h1></x-slot>
    <div class="mv-card overflow-x-auto"><table class="min-w-full text-sm"><thead><tr class="text-left text-ink-500"><th>Data</th><th>Tipo</th><th>Valor</th><th>Saldo</th></tr></thead><tbody>@foreach ($tenantFinancialAccount->financialTransactions as $transaction)<tr class="border-t border-ink-100"><td class="py-2">{{ $transaction->occurred_at?->format('d/m/Y H:i') }}</td><td>{{ $transaction->transaction_type->label() }}</td><td>{{ number_format((float) $transaction->amount, 2, ',', '.') }}</td><td>{{ number_format((float) $transaction->balance_after, 2, ',', '.') }}</td></tr>@endforeach</tbody></table></div>
</x-app-layout>
