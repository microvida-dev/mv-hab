<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Financeiro</h1></x-slot>
    <div class="grid gap-4">
        @forelse ($accounts as $account)
            <a class="mv-card block" href="{{ route('candidate.finance.accounts.show', $account) }}">
                <p class="font-semibold">{{ $account->account_number }}</p>
                <p class="text-sm text-ink-500">Saldo em aberto: {{ number_format((float) $account->current_balance, 2, ',', '.') }} EUR</p>
            </a>
        @empty
            <div class="mv-card"><p class="text-sm text-ink-600">Ainda não existe conta financeira ativa associada aos seus contratos.</p></div>
        @endforelse
    </div>
</x-app-layout>
