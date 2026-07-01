<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-mvhab-primary">Elegibilidade</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Novo conjunto de regras</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8"><div class="mv-surface p-6">
        <form method="POST" action="{{ route('backoffice.eligibility.rule-sets.store') }}">@csrf @include('backoffice.eligibility.rule-sets.partials.form')</form>
    </div></div></div>
</x-app-layout>
