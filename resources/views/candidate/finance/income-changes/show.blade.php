<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Declaração #{{ $incomeChangeDeclaration->id }}</h1></x-slot>
    <div class="mv-card space-y-4"><p>{{ $incomeChangeDeclaration->declared_reason }}</p><p>Estado: {{ $incomeChangeDeclaration->status->label() }}</p><form method="POST" action="{{ route('candidate.finance.income-changes.submit', $incomeChangeDeclaration) }}">@csrf<button class="mv-button-primary">Submeter</button></form></div>
</x-app-layout>
