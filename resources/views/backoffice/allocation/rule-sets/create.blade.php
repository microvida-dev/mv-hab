<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-mvhab-primary">Atribuição</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Criar regra de atribuição</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-3xl space-y-4 px-4 sm:px-6 lg:px-8"><x-flash-message /><form method="POST" action="{{ route('backoffice.allocation.rule-sets.store') }}" class="mv-surface space-y-4 p-6">@csrf @include('backoffice.allocation.rule-sets.form', ['allocationRuleSet' => null])<div class="flex justify-end"><button class="mv-button-primary">Guardar</button></div></form></div></div>
</x-app-layout>
