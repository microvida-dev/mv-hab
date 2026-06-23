<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Comprovativos</h1></x-slot>
    <div class="mv-card overflow-x-auto"><table class="min-w-full text-sm"><tbody>@foreach ($receipts as $receipt)<tr class="border-t border-ink-100"><td class="py-2"><a class="text-civic-700" href="{{ route('candidate.finance.receipts.show', $receipt) }}">{{ $receipt->receipt_number }}</a></td><td>{{ number_format((float) $receipt->total_amount, 2, ',', '.') }} EUR</td><td>{{ $receipt->status->label() }}</td></tr>@endforeach</tbody></table>{{ $receipts->links() }}</div>
</x-app-layout>
