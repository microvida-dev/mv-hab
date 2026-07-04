<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Fatura de inquilino"
            :title="$tenantInvoice->invoice_number"
            :description="$tenantInvoice->tenant?->name"
        />
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <x-mv.section title="Resumo da fatura">
            <div class="grid gap-4 md:grid-cols-4">
                <x-mv.stat-card label="Inquilino" :value="$tenantInvoice->tenant?->name ?? '-'" />
                <x-mv.stat-card label="Estado" :value="$tenantInvoice->status?->label() ?? '-'" />
                <x-mv.stat-card label="Vencimento" :value="$tenantInvoice->due_date?->format('d/m/Y') ?? '-'" />
                <x-mv.stat-card label="Em aberto" :value="number_format((float) $tenantInvoice->amount_outstanding, 2, ',', '.').' EUR'" />
            </div>
        </x-mv.section>
    </div>
</x-app-layout>
