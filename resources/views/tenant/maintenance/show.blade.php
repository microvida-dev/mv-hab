<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">{{ $maintenanceRequest->request_number }}</h1></x-slot>
    <div class="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="mv-card"><p class="text-sm text-ink-600">Os pedidos de manutenção serão analisados pelos serviços municipais, podendo ser solicitada informação adicional ou agendada vistoria/intervenção técnica.</p></div>
        <div class="mv-card">
            <p class="font-semibold">{{ $maintenanceRequest->title }}</p>
            <p class="mt-2 text-sm text-ink-600">{{ $maintenanceRequest->description }}</p>
            <p class="mt-4 text-sm text-ink-500">Estado: {{ $maintenanceRequest->status?->label() }}</p>
        </div>
    </div>
</x-app-layout>
