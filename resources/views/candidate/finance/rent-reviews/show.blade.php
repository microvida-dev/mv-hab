<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Revisão #{{ $rentReview->id }}</h1></x-slot>
    <div class="mv-card space-y-2"><p>Estado: {{ $rentReview->status->label() }}</p><p>Renda atual: {{ number_format((float) $rentReview->current_rent, 2, ',', '.') }} EUR</p><p>Renda aprovada: {{ $rentReview->approved_rent ? number_format((float) $rentReview->approved_rent, 2, ',', '.').' EUR' : 'Ainda não aprovada' }}</p></div>
</x-app-layout>
