<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-mvhab-primary">Modelos</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Novo modelo de comunicação</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8"><form method="POST" action="{{ route('backoffice.communications.templates.store') }}" class="rounded-2xl mv-surface p-6">@include('backoffice.communications.templates._form')</form></div></div>
</x-app-layout>
