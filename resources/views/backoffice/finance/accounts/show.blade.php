<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">{{ $tenantFinancialAccount->account_number }}</h1></x-slot>
    <div class="space-y-6">
        <div class="mv-card grid gap-4 md:grid-cols-4">
            <div><p class="text-xs text-ink-500">Candidato</p><p class="font-semibold">{{ $tenantFinancialAccount->tenant?->name }}</p></div>
            <div><p class="text-xs text-ink-500">Saldo</p><p class="font-semibold">{{ number_format((float) $tenantFinancialAccount->current_balance, 2, ',', '.') }} EUR</p></div>
            <div><p class="text-xs text-ink-500">Emitido</p><p class="font-semibold">{{ number_format((float) $tenantFinancialAccount->total_issued, 2, ',', '.') }} EUR</p></div>
            <div><p class="text-xs text-ink-500">Pago</p><p class="font-semibold">{{ number_format((float) $tenantFinancialAccount->total_paid, 2, ',', '.') }} EUR</p></div>
        </div>
        <div class="flex flex-wrap gap-3">
            <form method="POST" action="{{ route('backoffice.finance.accounts.detect-arrears', $tenantFinancialAccount) }}">@csrf<button class="mv-button-secondary">Detetar incumprimentos</button></form>
            <a class="mv-button-secondary" href="{{ route('backoffice.finance.accounts.statement', $tenantFinancialAccount) }}">Extrato</a>
        </div>
    </div>
</x-app-layout>
