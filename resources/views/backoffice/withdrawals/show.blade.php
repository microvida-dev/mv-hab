<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">Desistência controlada</h1></x-slot>
    <div class="py-8"><div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8"><section class="mv-surface p-6"><p class="font-semibold text-ink-900">{{ $withdrawal->status->label() }}</p><p class="mt-3 text-sm text-ink-600">{{ $withdrawal->reason }}</p><form method="POST" action="{{ route('backoffice.withdrawals.process', $withdrawal) }}" class="mt-5">@csrf<button class="rounded-md bg-civic-700 px-4 py-2 text-sm font-semibold text-white">Marcar como revista</button></form></section></div></div>
</x-app-layout>
