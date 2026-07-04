<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Operações de inquilino"
            title="Pagamentos de inquilino"
            description="Consulte pagamentos registados para faturas de inquilinos."
        />
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-4 px-4 py-6 sm:px-6 lg:px-8">
        <x-mv.section padding="p-0" class="overflow-hidden">
            @forelse ($payments as $payment)
                <a class="block border-b border-ink-100 p-4 transition hover:bg-ink-50" href="{{ route('backoffice.tenant-operations.payments.show', $payment) }}">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="font-semibold text-ink-900">{{ $payment->payment_number }} · {{ $payment->tenant?->name }}</p>
                            <p class="mt-1 text-sm text-ink-500">{{ number_format((float) $payment->amount, 2, ',', '.') }} EUR</p>
                        </div>

                        <x-mv.badge>{{ $payment->status?->label() }}</x-mv.badge>
                    </div>
                </a>
            @empty
                <x-mv.alert>Sem pagamentos operacionais.</x-mv.alert>
            @endforelse
        </x-mv.section>

        {{ $payments->links() }}
    </div>
</x-app-layout>
