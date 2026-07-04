<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Pagamento"
            :title="$tenantPayment->payment_number"
            description="Detalhe do pagamento registado."
        />
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-mv.section title="Resumo do pagamento">
            <div class="grid gap-4 md:grid-cols-4">
                <x-mv.stat-card label="Estado" :value="$tenantPayment->status?->label() ?? '-'" />
                <x-mv.stat-card label="Data" :value="$tenantPayment->payment_date?->format('d/m/Y') ?? '-'" />
                <x-mv.stat-card label="Valor" :value="number_format((float) $tenantPayment->amount, 2, ',', '.').' EUR'" />
                <x-mv.stat-card label="Método" :value="$tenantPayment->method ?? '-'" />
            </div>
        </x-mv.section>
    </div>
</x-app-layout>
