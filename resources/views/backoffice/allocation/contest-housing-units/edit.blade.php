<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-civic-700">Atribuição</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Editar habitação associada</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-3xl space-y-4 px-4 sm:px-6 lg:px-8">
        <x-flash-message />
        <form method="POST" action="{{ route('backoffice.allocation.contest-housing-units.update', $contestHousingUnit) }}" class="space-y-4 rounded-md border border-ink-100 bg-white p-6">
            @csrf @method('PATCH')
            @include('backoffice.allocation.contest-housing-units.form', ['contestHousingUnit' => $contestHousingUnit])
            <div class="flex justify-end"><button class="rounded-md bg-civic-700 px-4 py-2 text-sm font-semibold text-white">Atualizar</button></div>
        </form>
    </div></div>
</x-app-layout>
