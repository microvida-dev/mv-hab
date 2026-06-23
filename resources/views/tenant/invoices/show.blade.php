<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Fatura {{ $tenantInvoice->invoice_number }}</h1></x-slot>
    <div class="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="mv-card"><p class="text-sm text-ink-600">Os valores apresentados refletem a informação registada na plataforma. Em caso de divergência, prevalece a validação dos serviços municipais competentes.</p></div>
        <div class="mv-card">
            <dl class="grid gap-4 md:grid-cols-4">
                <div><dt class="text-xs text-ink-500">Estado</dt><dd class="font-semibold">{{ $tenantInvoice->status?->label() }}</dd></div>
                <div><dt class="text-xs text-ink-500">Emissão</dt><dd class="font-semibold">{{ $tenantInvoice->issue_date?->format('d/m/Y') }}</dd></div>
                <div><dt class="text-xs text-ink-500">Vencimento</dt><dd class="font-semibold">{{ $tenantInvoice->due_date?->format('d/m/Y') }}</dd></div>
                <div><dt class="text-xs text-ink-500">Em aberto</dt><dd class="font-semibold">{{ number_format((float) $tenantInvoice->amount_outstanding, 2, ',', '.') }} EUR</dd></div>
            </dl>
        </div>
    </div>
</x-app-layout>
