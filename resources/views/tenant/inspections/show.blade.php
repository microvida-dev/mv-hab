<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Vistoria"
            :title="$propertyInspection->inspection_number"
            description="Detalhe da vistoria comunicada pelos serviços municipais."
        />
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-mv.alert>
            O agendamento de vistoria está sujeito à disponibilidade dos serviços municipais e à confirmação das partes envolvidas.
        </x-mv.alert>

        <x-mv.section title="Detalhe da vistoria">
            <div class="flex flex-wrap items-center gap-3">
                <p class="font-semibold text-ink-900">{{ $propertyInspection->inspection_type?->label() ?? $propertyInspection->inspection_type }}</p>
                <x-mv.badge>{{ $propertyInspection->status?->label() }}</x-mv.badge>
            </div>

            <p class="mt-5 text-sm leading-6 text-ink-700">{{ $propertyInspection->summary }}</p>
        </x-mv.section>
    </div>
</x-app-layout>
