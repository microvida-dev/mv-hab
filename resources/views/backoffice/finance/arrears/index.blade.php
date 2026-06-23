<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Incumprimentos</h1></x-slot>
    <div class="mv-card overflow-x-auto"><table class="min-w-full text-sm"><thead><tr class="text-left text-ink-500"><th>Prestação</th><th>Candidato</th><th>Valor</th><th>Estado</th></tr></thead><tbody>@foreach ($arrears as $arrear)<tr class="border-t border-ink-100"><td class="py-2"><a class="text-civic-700" href="{{ route('backoffice.finance.arrears.show', $arrear) }}">{{ $arrear->rentInstallment?->reference }}</a></td><td>{{ $arrear->tenant?->name }}</td><td>{{ number_format((float) $arrear->outstanding_amount, 2, ',', '.') }} EUR</td><td>{{ $arrear->status->label() }}</td></tr>@endforeach</tbody></table>{{ $arrears->links() }}</div>
</x-app-layout>
