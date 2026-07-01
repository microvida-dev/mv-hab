<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Contas financeiras</h1></x-slot>
    <div class="space-y-6">
        <form method="POST" action="{{ route('backoffice.finance.accounts.store') }}" class="mv-card grid gap-3 md:grid-cols-[1fr_auto]">
            @csrf
            <input class="mv-input" name="lease_contract_id" placeholder="ID do contrato ativo" required>
            <button class="mv-button-primary">Criar conta</button>
        </form>
        <div class="mv-card overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead><tr class="text-left text-ink-500"><th class="py-2">Conta</th><th>Candidato</th><th>Saldo</th><th>Estado</th></tr></thead>
                <tbody>
                    @foreach ($accounts as $account)
                        <tr class="border-t border-ink-100">
                            <td class="py-3"><a class="font-semibold text-mvhab-primary" href="{{ route('backoffice.finance.accounts.show', $account) }}">{{ $account->account_number }}</a></td>
                            <td>{{ $account->tenant?->name }}</td>
                            <td>{{ number_format((float) $account->current_balance, 2, ',', '.') }} EUR</td>
                            <td>{{ $account->status->label() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $accounts->links() }}
        </div>
    </div>
</x-app-layout>
