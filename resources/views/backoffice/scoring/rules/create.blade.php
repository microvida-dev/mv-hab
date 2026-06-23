<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-civic-700">{{ $criterion->name }}</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Nova regra</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8"><div class="rounded-md border border-ink-100 bg-white p-6">@include('backoffice.scoring.rules._form', ['action' => route('backoffice.scoring.rules.store', $criterion)])</div></div></div>
</x-app-layout>
