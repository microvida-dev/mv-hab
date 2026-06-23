<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Pagamento {{ $tenantPayment->payment_number }}</h1></x-slot>
    <div class="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="mv-card">
            <dl class="grid gap-4 md:grid-cols-4">
                <div><dt class="text-xs text-ink-500">Estado</dt><dd class="font-semibold">{{ $tenantPayment->status?->label() }}</dd></div>
                <div><dt class="text-xs text-ink-500">Data</dt><dd class="font-semibold">{{ $tenantPayment->payment_date?->format('d/m/Y') }}</dd></div>
                <div><dt class="text-xs text-ink-500">Valor</dt><dd class="font-semibold">{{ number_format((float) $tenantPayment->amount, 2, ',', '.') }} EUR</dd></div>
                <div><dt class="text-xs text-ink-500">Método</dt><dd class="font-semibold">{{ $tenantPayment->method }}</dd></div>
            </dl>
        </div>
    </div>
</x-app-layout>
