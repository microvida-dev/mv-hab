<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">{{ $defaultNotice->notice_number }}</h1></x-slot>
    <div class="mv-card space-y-3"><p class="font-semibold">{{ $defaultNotice->subject }}</p><p>{{ $defaultNotice->body }}</p><p>Valor: {{ number_format((float) $defaultNotice->amount_due, 2, ',', '.') }} EUR</p></div>
</x-app-layout>
