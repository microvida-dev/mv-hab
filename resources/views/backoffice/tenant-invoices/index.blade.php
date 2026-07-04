<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Operações de inquilino"
            title="Faturas de inquilino"
            description="Consulte faturas e rendas operacionais registadas para inquilinos."
        />
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-4 px-4 py-6 sm:px-6 lg:px-8">
        <x-mv.alert>
            Os valores apresentados refletem a informação registada na plataforma. Em caso de divergência, prevalece a validação dos serviços municipais competentes.
        </x-mv.alert>

        <x-mv.section padding="p-0" class="overflow-hidden">
            @forelse ($invoices as $invoice)
                <a class="block border-b border-ink-100 p-4 transition hover:bg-ink-50" href="{{ route('backoffice.tenant-operations.invoices.show', $invoice) }}">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="font-semibold text-ink-900">{{ $invoice->invoice_number }} · {{ $invoice->tenant?->name }}</p>
                            <p class="mt-1 text-sm text-ink-500">{{ number_format((float) $invoice->amount_outstanding, 2, ',', '.') }} EUR</p>
                        </div>

                        <x-mv.badge>{{ $invoice->status?->label() }}</x-mv.badge>
                    </div>
                </a>
            @empty
                <x-mv.alert>Sem faturas operacionais.</x-mv.alert>
            @endforelse
        </x-mv.section>

        {{ $invoices->links() }}
    </div>
</x-app-layout>
