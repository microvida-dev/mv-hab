<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Faturas de inquilino</h1></x-slot>
    <div class="mx-auto grid max-w-7xl gap-4 px-4 py-6 sm:px-6 lg:px-8">
        <div class="mv-card"><p class="text-sm text-ink-600">Os valores apresentados refletem a informação registada na plataforma. Em caso de divergência, prevalece a validação dos serviços municipais competentes.</p></div>
        @forelse ($invoices as $invoice)
            <a class="mv-card block" href="{{ route('backoffice.tenant-operations.invoices.show', $invoice) }}">
                <p class="font-semibold">{{ $invoice->invoice_number }} · {{ $invoice->tenant?->name }}</p>
                <p class="text-sm text-ink-500">{{ $invoice->status?->label() }} · {{ number_format((float) $invoice->amount_outstanding, 2, ',', '.') }} EUR</p>
            </a>
        @empty
            <div class="mv-card"><p class="text-sm text-ink-600">Sem faturas operacionais.</p></div>
        @endforelse
        {{ $invoices->links() }}
    </div>
</x-app-layout>
