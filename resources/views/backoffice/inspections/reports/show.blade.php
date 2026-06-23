<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">{{ $propertyInspectionReport->report_number }}</h1></x-slot>
    <div class="mv-card space-y-3"><p>Estado: <strong>{{ $propertyInspectionReport->status->label() }}</strong></p><p>Vistoria: {{ $propertyInspectionReport->inspection?->inspection_number }}</p><a class="mv-button-secondary" href="{{ route('backoffice.inspections.reports.download', $propertyInspectionReport) }}">Descarregar HTML</a><form method="POST" action="{{ route('backoffice.inspections.reports.validate', $propertyInspectionReport) }}">@csrf<button class="mv-button-primary">Validar auto</button></form></div>
</x-app-layout>
