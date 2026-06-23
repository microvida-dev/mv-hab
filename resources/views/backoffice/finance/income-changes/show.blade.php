<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Declaração #{{ $incomeChangeDeclaration->id }}</h1></x-slot>
    <div class="mv-card space-y-4"><p>{{ $incomeChangeDeclaration->declared_reason }}</p><p>Estado: {{ $incomeChangeDeclaration->status->label() }}</p><form method="POST" action="{{ route('backoffice.finance.income-changes.accept', $incomeChangeDeclaration) }}">@csrf<button class="mv-button-secondary">Aceitar</button></form><form method="POST" action="{{ route('backoffice.finance.income-changes.reject', $incomeChangeDeclaration) }}" class="flex gap-2">@csrf<input class="mv-input" name="reason" placeholder="Motivo" required><button class="mv-button-secondary">Rejeitar</button></form></div>
</x-app-layout>
