<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">{{ $defaultNotice->notice_number }}</h1></x-slot>
    <div class="mv-card space-y-4"><p class="font-semibold">{{ $defaultNotice->subject }}</p><p>{{ $defaultNotice->body }}</p><p>Estado: {{ $defaultNotice->status->label() }}</p><form method="POST" action="{{ route('backoffice.finance.default-notices.issue', $defaultNotice) }}">@csrf<button class="mv-button-secondary">Emitir</button></form></div>
</x-app-layout>
