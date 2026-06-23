<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Prestações</h1></x-slot>
    <div class="mv-card overflow-x-auto"><table class="min-w-full text-sm"><tbody>@foreach ($installments as $installment)<tr class="border-t border-ink-100"><td class="py-2"><a class="text-civic-700" href="{{ route('candidate.finance.installments.show', $installment) }}">{{ $installment->reference }}</a></td><td>{{ $installment->due_date?->format('d/m/Y') }}</td><td>{{ number_format((float) $installment->amount_outstanding, 2, ',', '.') }} EUR</td><td>{{ $installment->status->label() }}</td></tr>@endforeach</tbody></table>{{ $installments->links() }}</div>
</x-app-layout>
