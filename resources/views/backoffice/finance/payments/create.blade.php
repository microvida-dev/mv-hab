<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Registar pagamento</h1></x-slot>
    <form method="POST" action="{{ route('backoffice.finance.payments.store') }}" class="mv-card grid gap-4">
        @csrf
        <select class="mv-input" name="tenant_financial_account_id" required>@foreach ($accounts as $account)<option value="{{ $account->id }}">{{ $account->account_number }} · {{ $account->tenant?->name }}</option>@endforeach</select>
        <input class="mv-input" name="amount" type="number" step="0.01" min="0.01" placeholder="Valor" required>
        <input class="mv-input" name="payment_date" type="date" value="{{ now()->toDateString() }}" required>
        <input class="mv-input" name="external_reference" placeholder="Referência externa opcional">
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="confirm_now" value="1"> Confirmar já</label>
        <button class="mv-button-primary">Guardar</button>
    </form>
</x-app-layout>
