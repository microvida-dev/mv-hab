<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Pagamentos</h1></x-slot>
    <div class="mv-card overflow-x-auto"><table class="min-w-full text-sm"><tbody>@foreach ($payments as $payment)<tr class="border-t border-ink-100"><td class="py-2"><a class="text-civic-700" href="{{ route('candidate.finance.payments.show', $payment) }}">{{ $payment->payment_number }}</a></td><td>{{ $payment->payment_date?->format('d/m/Y') }}</td><td>{{ number_format((float) $payment->amount, 2, ',', '.') }} EUR</td><td>{{ $payment->status->label() }}</td></tr>@endforeach</tbody></table>{{ $payments->links() }}</div>
</x-app-layout>
