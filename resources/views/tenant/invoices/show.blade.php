<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Fatura"
            :title="$tenantInvoice->invoice_number"
            description="Detalhe da fatura ou renda registada na plataforma."
        />
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-mv.alert>
            Os valores apresentados refletem a informação registada na plataforma. Em caso de divergência, prevalece a validação dos serviços municipais competentes.
        </x-mv.alert>

        <x-mv.section title="Resumo da fatura">
            <div class="grid gap-4 md:grid-cols-4">
                <x-mv.stat-card label="Estado" :value="$tenantInvoice->status?->label() ?? '-'" />
                <x-mv.stat-card label="Emissão" :value="$tenantInvoice->issue_date?->format('d/m/Y') ?? '-'" />
                <x-mv.stat-card label="Vencimento" :value="$tenantInvoice->due_date?->format('d/m/Y') ?? '-'" />
                <x-mv.stat-card label="Em aberto" :value="number_format((float) $tenantInvoice->amount_outstanding, 2, ',', '.').' EUR'" />
            </div>
        </x-mv.section>
    </div>
</x-app-layout>
