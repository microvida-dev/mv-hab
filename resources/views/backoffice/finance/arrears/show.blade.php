<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Incumprimento #{{ $arrear->id }}</h1></x-slot>
    <div class="mv-card space-y-3"><p>Valor em aberto: <strong>{{ number_format((float) $arrear->outstanding_amount, 2, ',', '.') }} EUR</strong></p><p>Estado: <strong>{{ $arrear->status->label() }}</strong></p><form method="POST" action="{{ route('backoffice.finance.arrears.close', $arrear) }}" class="grid gap-2">@csrf<input class="mv-input" name="reason" placeholder="Justificação" required><button class="mv-button-secondary">Fechar</button></form></div>
</x-app-layout>
