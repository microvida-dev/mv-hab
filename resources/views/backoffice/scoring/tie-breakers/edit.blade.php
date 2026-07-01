<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-mvhab-primary">{{ $ruleSet->name }}</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Editar regra de desempate</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8"><div class="mv-surface p-6">@include('backoffice.scoring.tie-breakers._form', ['action' => route('backoffice.scoring.tie-breakers.update', $rule), 'method' => 'PUT'])</div></div></div>
</x-app-layout>
