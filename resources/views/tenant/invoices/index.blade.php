<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Faturas e rendas</h1></x-slot>
    <div class="mx-auto grid max-w-7xl gap-4 px-4 py-6 sm:px-6 lg:px-8">
        <div class="mv-card"><p class="text-sm text-ink-600">Os valores apresentados refletem a informação registada na plataforma. Em caso de divergência, prevalece a validação dos serviços municipais competentes.</p></div>
        @forelse ($invoices as $invoice)
            <a class="mv-card block" href="{{ route('tenant.invoices.show', $invoice) }}">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="font-semibold">{{ $invoice->invoice_number }}</p>
                        <p class="text-sm text-ink-500">{{ $invoice->period_month }}/{{ $invoice->period_year }} · {{ $invoice->charge_type?->label() }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold">{{ number_format((float) $invoice->amount_outstanding, 2, ',', '.') }} EUR</p>
                        <p class="text-sm text-ink-500">{{ $invoice->status?->label() }}</p>
                    </div>
                </div>
            </a>
        @empty
            <div class="mv-card"><p class="text-sm text-ink-600">Sem faturas registadas.</p></div>
        @endforelse
        {{ $invoices->links() }}
    </div>
</x-app-layout>
