<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Área do Inquilino"
            title="Faturas e rendas"
            description="Consulte rendas e faturas registadas na plataforma."
        />
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-4 px-4 py-6 sm:px-6 lg:px-8">
        <x-mv.alert>
            Os valores apresentados refletem a informação registada na plataforma. Em caso de divergência, prevalece a validação dos serviços municipais competentes.
        </x-mv.alert>

        <x-mv.section padding="p-0" class="overflow-hidden">
            @forelse ($invoices as $invoice)
                <a class="block border-b border-ink-100 p-4 transition hover:bg-ink-50" href="{{ route('tenant.invoices.show', $invoice) }}">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="font-semibold text-ink-900">{{ $invoice->invoice_number }}</p>
                            <p class="mt-1 text-sm text-ink-500">{{ $invoice->period_month }}/{{ $invoice->period_year }} · {{ $invoice->charge_type?->label() }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-ink-900">{{ number_format((float) $invoice->amount_outstanding, 2, ',', '.') }} EUR</p>
                            <x-mv.badge>{{ $invoice->status?->label() }}</x-mv.badge>
                        </div>
                    </div>
                </a>
            @empty
                <x-mv.alert>Sem faturas registadas.</x-mv.alert>
            @endforelse
        </x-mv.section>

        {{ $invoices->links() }}
    </div>
</x-app-layout>
