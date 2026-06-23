<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Intervenção #{{ $maintenanceIntervention->id }}</h1></x-slot>
    <div class="mv-card space-y-3"><p>Estado: <strong>{{ $maintenanceIntervention->status->label() }}</strong></p><p>{{ $maintenanceIntervention->work_description }}</p><p>{{ $maintenanceIntervention->result_summary }}</p></div>
</x-app-layout>
