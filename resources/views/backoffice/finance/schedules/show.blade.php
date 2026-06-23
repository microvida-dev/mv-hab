<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Plano de renda #{{ $rentSchedule->id }}</h1></x-slot>
    <div class="space-y-6">
        <div class="mv-card"><p class="font-semibold">{{ $rentSchedule->status->label() }} · {{ number_format((float) $rentSchedule->monthly_rent, 2, ',', '.') }} EUR</p></div>
        <div class="mv-card overflow-x-auto">
            <table class="min-w-full text-sm"><thead><tr class="text-left text-ink-500"><th>Ref.</th><th>Vencimento</th><th>Valor</th><th>Em aberto</th><th>Estado</th></tr></thead><tbody>
                @foreach ($rentSchedule->installments as $installment)<tr class="border-t border-ink-100"><td class="py-2">{{ $installment->reference }}</td><td>{{ $installment->due_date?->format('d/m/Y') }}</td><td>{{ number_format((float) $installment->amount_due, 2, ',', '.') }}</td><td>{{ number_format((float) $installment->amount_outstanding, 2, ',', '.') }}</td><td>{{ $installment->status->label() }}</td></tr>@endforeach
            </tbody></table>
        </div>
    </div>
</x-app-layout>
