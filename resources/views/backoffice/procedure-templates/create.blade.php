<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-mvhab-primary">Minuta</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Nova minuta de procedimento</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8"><form method="POST" action="{{ route('backoffice.procedure-templates.store') }}" class="mv-surface space-y-6 p-6">@csrf @include('backoffice.procedure-templates._form')<button class="mv-button-primary">Guardar minuta</button></form></div></div>
</x-app-layout>
