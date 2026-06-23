<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Vistoria {{ $propertyInspection->inspection_number }}</h1></x-slot>
    <div class="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="mv-card"><p class="text-sm text-ink-600">O agendamento de vistoria está sujeito à disponibilidade dos serviços municipais e à confirmação das partes envolvidas.</p></div>
        <div class="mv-card">
            <p class="font-semibold">{{ $propertyInspection->inspection_type?->label() ?? $propertyInspection->inspection_type }}</p>
            <p class="text-sm text-ink-500">Estado: {{ $propertyInspection->status?->label() }}</p>
            <p class="mt-3 text-sm text-ink-600">{{ $propertyInspection->summary }}</p>
        </div>
    </div>
</x-app-layout>
