<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Pagamento de inquilino"
            :title="$tenantPayment->payment_number"
            :description="$tenantPayment->tenant?->name"
        />
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-mv.section title="Resumo do pagamento">
            <div class="grid gap-4 md:grid-cols-4">
                <x-mv.stat-card label="Inquilino" :value="$tenantPayment->tenant?->name ?? '-'" />
                <x-mv.stat-card label="Estado" :value="$tenantPayment->status?->label() ?? '-'" />
                <x-mv.stat-card label="Data" :value="$tenantPayment->payment_date?->format('d/m/Y') ?? '-'" />
                <x-mv.stat-card label="Valor" :value="number_format((float) $tenantPayment->amount, 2, ',', '.').' EUR'" />
            </div>
        </x-mv.section>
    </div>
</x-app-layout>
