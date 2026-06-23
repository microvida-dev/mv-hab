<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-civic-700">Notificação</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $officialNotification->subject }}</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8"><div class="rounded-md border border-ink-100 bg-white p-6"><p class="text-sm text-ink-500">{{ $officialNotification->notification_type->label() }} · {{ $officialNotification->status->label() }}</p><p class="mt-4 whitespace-pre-line text-sm">{{ $officialNotification->body }}</p></div><form method="POST" action="{{ route('candidate.official-notifications.mark-read', $officialNotification) }}">@csrf<x-secondary-button>Marcar como lida</x-secondary-button></form></div></div>
</x-app-layout>

