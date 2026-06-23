<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Planos de renda</h1></x-slot>
    <div class="mv-card overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead><tr class="text-left text-ink-500"><th class="py-2">Contrato</th><th>Período</th><th>Renda</th><th>Estado</th></tr></thead>
            <tbody>@foreach ($schedules as $schedule)<tr class="border-t border-ink-100"><td class="py-3"><a class="text-civic-700" href="{{ route('backoffice.finance.schedules.show', $schedule) }}">#{{ $schedule->lease_contract_id }}</a></td><td>{{ $schedule->starts_on?->format('d/m/Y') }} - {{ $schedule->ends_on?->format('d/m/Y') }}</td><td>{{ number_format((float) $schedule->monthly_rent, 2, ',', '.') }} EUR</td><td>{{ $schedule->status->label() }}</td></tr>@endforeach</tbody>
        </table>
        {{ $schedules->links() }}
    </div>
</x-app-layout>
