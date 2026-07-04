<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Visitas abertas"
            title="Editar visita aberta"
            description="Atualize a janela de visita, o concurso ou fogo associado e a capacidade dos horários."
        />
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
