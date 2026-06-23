<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Criar aviso</h1></x-slot>
    <form method="POST" action="{{ route('backoffice.finance.default-notices.store') }}" class="mv-card grid gap-4">@csrf<select class="mv-input" name="arrear_id" required>@foreach ($arrears as $arrear)<option value="{{ $arrear->id }}">#{{ $arrear->id }} · {{ $arrear->tenant?->name }} · {{ number_format((float) $arrear->outstanding_amount, 2, ',', '.') }} EUR</option>@endforeach</select><input class="mv-input" name="subject" placeholder="Assunto" required><textarea class="mv-input" name="body" placeholder="Texto do aviso" required></textarea><button class="mv-button-primary">Guardar</button></form>
</x-app-layout>
