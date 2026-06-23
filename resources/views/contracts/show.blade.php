<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Contrato #{{ $contract->id }}</h2>
            <a href="{{ route('contracts.edit', $contract) }}" class="inline-flex items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Editar
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <x-flash-message />

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
                    <h3 class="text-lg font-semibold text-slate-900">Dados do contrato</h3>
                    <dl class="mt-6 grid gap-6 md:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Munícipe</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $contract->citizen->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Habitação</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $contract->housingUnit->code }} - {{ $contract->housingUnit->address }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Início</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $contract->start_date?->format('d/m/Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Fim</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $contract->end_date?->format('d/m/Y') ?: 'Sem data definida' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Renda mensal</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ number_format((float) $contract->monthly_rent, 2, ',', '.') }} €</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Estado</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $contract->status->label() }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Documentos</h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($contract->documents as $document)
                            <a href="{{ route('documents.show', $document) }}" class="block rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                                <p class="font-medium text-slate-900">{{ $document->name }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $document->mime_type }}</p>
                            </a>
                        @empty
                            <p class="text-sm text-slate-500">Sem documentos associados.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-900">Pagamentos</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($contract->payments as $payment)
                        <a href="{{ route('payments.show', $payment) }}" class="block rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                            <p class="font-medium text-slate-900">{{ $payment->reference }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ number_format((float) $payment->amount, 2, ',', '.') }} € - {{ $payment->status->label() }}</p>
                        </a>
                    @empty
                        <p class="text-sm text-slate-500">Sem pagamentos associados.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
