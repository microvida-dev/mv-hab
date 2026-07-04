<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Manutenção"
            :title="'Intervenção #'.$maintenanceIntervention->id"
            description="Detalhe da intervenção técnica."
        />
    </x-slot>

    <x-mv.section title="Resumo da intervenção">
        <x-mv.badge>{{ $maintenanceIntervention->status->label() }}</x-mv.badge>
        <p class="mt-4 text-sm leading-6 text-ink-700">{{ $maintenanceIntervention->work_description }}</p>
        <p class="mt-2 text-sm text-ink-500">{{ $maintenanceIntervention->result_summary }}</p>
    </x-mv.section>
</x-app-layout>
