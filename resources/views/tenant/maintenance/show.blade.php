<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Manutenção"
            :title="$maintenanceRequest->request_number"
            :description="$maintenanceRequest->title"
        />
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-mv.alert>
            Os pedidos de manutenção serão analisados pelos serviços municipais, podendo ser solicitada informação adicional ou agendada vistoria/intervenção técnica.
        </x-mv.alert>

        <x-mv.section title="Detalhe do pedido">
            <x-mv.badge>{{ $maintenanceRequest->status?->label() }}</x-mv.badge>

            <p class="mt-5 text-sm leading-6 text-ink-700">{{ $maintenanceRequest->description }}</p>
        </x-mv.section>
    </div>
</x-app-layout>
