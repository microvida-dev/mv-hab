<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-civic-700">Modelos</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Editar {{ $notificationTemplate->name }}</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8"><form method="POST" action="{{ route('backoffice.communications.templates.update', $notificationTemplate) }}" class="rounded-md border border-ink-100 bg-white p-6">@include('backoffice.communications.templates._form')</form></div></div>
</x-app-layout>
