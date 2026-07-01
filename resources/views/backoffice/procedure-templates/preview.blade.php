<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-mvhab-primary">Pré-visualização</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $procedureTemplate->name }}</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8"><section class="mv-surface p-6"><div class="prose max-w-none">{!! $preview !!}</div></section><section class="mv-surface p-6"><h2 class="text-lg font-semibold text-ink-900">Variáveis usadas</h2><pre class="mt-4 overflow-auto rounded-2xl bg-ink-50 p-4 text-xs">{{ json_encode($variables, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></section></div></div>
</x-app-layout>
