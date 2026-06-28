<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Visitas abertas</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Editar visita aberta</h1>
            <p class="mt-1 text-sm text-ink-500">Atualize a janela de visita, o concurso ou fogo associado e a capacidade dos horários.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @include('backoffice.visit-availabilities.partials.form', [
                'action' => route('backoffice.visit-availabilities.update', $availability),
                'method' => 'PATCH',
                'availability' => $availability,
            ])
        </div>
    </div>
</x-app-layout>
