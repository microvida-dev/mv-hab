<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Pagamentos de renda</h1></x-slot>
    <div class="space-y-4">
        <a class="mv-button-primary" href="{{ route('backoffice.finance.payments.create') }}">Registar pagamento</a>
        <div class="mv-card overflow-x-auto"><table class="min-w-full text-sm"><thead><tr class="text-left text-ink-500"><th>Número</th><th>Candidato</th><th>Valor</th><th>Estado</th></tr></thead><tbody>
            @foreach ($payments as $payment)<tr class="border-t border-ink-100"><td class="py-2"><a class="text-mvhab-primary" href="{{ route('backoffice.finance.payments.show', $payment) }}">{{ $payment->payment_number }}</a></td><td>{{ $payment->tenant?->name }}</td><td>{{ number_format((float) $payment->amount, 2, ',', '.') }} EUR</td><td>{{ $payment->status->label() }}</td></tr>@endforeach
        </tbody></table>{{ $payments->links() }}</div>
    </div>
</x-app-layout>
