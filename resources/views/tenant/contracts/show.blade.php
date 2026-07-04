<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Contrato"
            :title="'Contrato '.$contract->contract_number"
            :description="$contract->housingUnit?->address ?? 'Morada não registada.'"
        />
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-mv.section title="Dados do contrato">
            <div class="grid gap-4 md:grid-cols-3">
                <x-mv.stat-card label="Estado" :value="$contract->status?->label() ?? $contract->status" />
                <x-mv.stat-card label="Renda mensal" :value="number_format((float) $contract->monthly_rent, 2, ',', '.').' EUR'" />
                <x-mv.stat-card label="Habitação" :value="$contract->housingUnit?->code ?? '-'" />
            </div>
        </x-mv.section>

        <x-mv.alert>
            {{ $contract->housingUnit?->address ?? 'Morada não registada.' }}
        </x-mv.alert>
    </div>
</x-app-layout>
