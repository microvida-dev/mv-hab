<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Pagamentos de inquilino</h1></x-slot>
    <div class="mx-auto grid max-w-7xl gap-4 px-4 py-6 sm:px-6 lg:px-8">
        @forelse ($payments as $payment)
            <a class="mv-card block" href="{{ route('backoffice.tenant-operations.payments.show', $payment) }}">
                <p class="font-semibold">{{ $payment->payment_number }} · {{ $payment->tenant?->name }}</p>
                <p class="text-sm text-ink-500">{{ $payment->status?->label() }} · {{ number_format((float) $payment->amount, 2, ',', '.') }} EUR</p>
            </a>
        @empty
            <div class="mv-card"><p class="text-sm text-ink-600">Sem pagamentos operacionais.</p></div>
        @endforelse
        {{ $payments->links() }}
    </div>
</x-app-layout>
