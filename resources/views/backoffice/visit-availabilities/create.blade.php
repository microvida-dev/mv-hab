<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Visitas abertas"
            title="Criar visita aberta"
            description="Associe a janela de visita a um concurso ou fogo e defina duração e capacidade dos horários."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @include('backoffice.visit-availabilities.partials.form', [
                'action' => route('backoffice.visit-availabilities.store'),
                'method' => 'POST',
                'availability' => null,
            ])
        </div>
    </div>
</x-app-layout>
