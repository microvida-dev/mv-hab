<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Auto de vistoria"
            :title="$propertyInspectionReport->report_number"
            :description="$propertyInspectionReport->inspection?->inspection_number"
        >
            <x-slot name="actions">
                <a class="mv-button-secondary" href="{{ route('backoffice.inspections.reports.download', $propertyInspectionReport) }}">Descarregar HTML</a>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <x-mv.section title="Resumo do auto">
        <div class="grid gap-4 md:grid-cols-2">
            <x-mv.stat-card label="Estado" :value="$propertyInspectionReport->status->label()" />
            <x-mv.stat-card label="Vistoria" :value="$propertyInspectionReport->inspection?->inspection_number ?? '-'" />
        </div>

        <form method="POST" action="{{ route('backoffice.inspections.reports.validate', $propertyInspectionReport) }}" class="mt-5">
            @csrf
            <button class="mv-button-primary">Validar auto</button>
        </form>
    </x-mv.section>
</x-app-layout>
