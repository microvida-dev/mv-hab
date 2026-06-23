<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-slate-800">{{ $payment->reference }}</h2>
            <a href="{{ route('payments.edit', $payment) }}" class="inline-flex items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Editar
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
            <x-flash-message />

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900">Dados do pagamento</h3>
                <dl class="mt-6 grid gap-6 md:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Contrato</dt>
                        <dd class="mt-1 text-sm text-slate-900">#{{ $payment->contract->id }} - {{ $payment->contract->citizen->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Habitação</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $payment->contract->housingUnit->code }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Valor</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ number_format((float) $payment->amount, 2, ',', '.') }} €</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Estado</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $payment->status->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Vencimento</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $payment->due_date?->format('d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Pago em</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $payment->paid_at?->format('d/m/Y H:i') ?: 'Ainda não pago' }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>
