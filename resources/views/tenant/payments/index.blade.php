<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Pagamentos</h1></x-slot>
    <div class="mx-auto grid max-w-7xl gap-4 px-4 py-6 sm:px-6 lg:px-8">
        <div class="mv-card"><p class="text-sm text-ink-600">Os valores apresentados refletem a informação registada na plataforma. Em caso de divergência, prevalece a validação dos serviços municipais competentes.</p></div>
        @forelse ($payments as $payment)
            <a class="mv-card block" href="{{ route('tenant.payments.show', $payment) }}">
                <p class="font-semibold">{{ $payment->payment_number }}</p>
                <p class="text-sm text-ink-500">{{ $payment->payment_date?->format('d/m/Y') }} · {{ number_format((float) $payment->amount, 2, ',', '.') }} EUR · {{ $payment->status?->label() }}</p>
            </a>
        @empty
            <div class="mv-card"><p class="text-sm text-ink-600">Sem pagamentos registados.</p></div>
        @endforelse
        {{ $payments->links() }}
    </div>
</x-app-layout>
