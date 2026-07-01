<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Prestações de renda</h1></x-slot>
    <div class="mv-card overflow-x-auto">
        <table class="min-w-full text-sm"><thead><tr class="text-left text-ink-500"><th>Ref.</th><th>Candidato</th><th>Vencimento</th><th>Em aberto</th><th>Estado</th></tr></thead><tbody>
            @foreach ($installments as $installment)<tr class="border-t border-ink-100"><td class="py-2"><a class="text-mvhab-primary" href="{{ route('backoffice.finance.installments.show', $installment) }}">{{ $installment->reference }}</a></td><td>{{ $installment->tenant?->name }}</td><td>{{ $installment->due_date?->format('d/m/Y') }}</td><td>{{ number_format((float) $installment->amount_outstanding, 2, ',', '.') }} EUR</td><td>{{ $installment->status->label() }}</td></tr>@endforeach
        </tbody></table>
        {{ $installments->links() }}
    </div>
</x-app-layout>
