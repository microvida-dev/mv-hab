<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Acordos de regularização</h1></x-slot>
    <a class="mv-button-primary" href="{{ route('backoffice.finance.regularization-agreements.create') }}">Criar acordo</a>
    <div class="mv-card mt-4 overflow-x-auto"><table class="min-w-full text-sm"><tbody>@foreach ($agreements as $agreement)<tr class="border-t border-ink-100"><td class="py-2"><a class="text-civic-700" href="{{ route('backoffice.finance.regularization-agreements.show', $agreement) }}">{{ $agreement->agreement_number }}</a></td><td>{{ $agreement->tenant?->name }}</td><td>{{ number_format((float) $agreement->total_amount, 2, ',', '.') }} EUR</td><td>{{ $agreement->status->label() }}</td></tr>@endforeach</tbody></table>{{ $agreements->links() }}</div>
</x-app-layout>
