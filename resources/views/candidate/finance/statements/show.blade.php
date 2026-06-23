<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">{{ $tenantFinancialAccount->account_number }}</h1></x-slot>
    <div class="mv-card grid gap-4 md:grid-cols-3"><div><p class="text-xs text-ink-500">Saldo</p><p class="font-semibold">{{ number_format((float) $tenantFinancialAccount->current_balance, 2, ',', '.') }} EUR</p></div><div><p class="text-xs text-ink-500">Emitido</p><p class="font-semibold">{{ number_format((float) $tenantFinancialAccount->total_issued, 2, ',', '.') }} EUR</p></div><div><p class="text-xs text-ink-500">Pago</p><p class="font-semibold">{{ number_format((float) $tenantFinancialAccount->total_paid, 2, ',', '.') }} EUR</p></div></div>
</x-app-layout>
