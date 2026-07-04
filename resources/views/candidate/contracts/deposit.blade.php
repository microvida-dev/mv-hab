<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Contrato"
            title="Caução"
            :description="$leaseContract->contract_number"
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <x-mv.section title="Dados da caução">
                @if ($leaseContract->deposit)
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-mv.stat-card
                            label="Valor da caução"
                            :value="$leaseContract->deposit->amount.' '.$leaseContract->deposit->currency"
                        />

                        <x-mv.stat-card
                            label="Estado"
                            :value="$leaseContract->deposit->status->label()"
                        />

                        <x-mv.stat-card
                            label="Data de pedido"
                            :value="$leaseContract->deposit->requested_at?->format('d/m/Y H:i') ?? '-'"
                        />

                        <x-mv.stat-card
                            label="Data de pagamento registado"
                            :value="$leaseContract->deposit->paid_at?->format('d/m/Y H:i') ?? '-'"
                        />
                    </div>

                    <div class="mt-5 rounded-2xl border border-ink-100 p-4 text-sm">
                        <p class="text-ink-500">Observações</p>
                        <p class="mt-1 font-semibold text-ink-900">{{ $leaseContract->deposit->notes ?? '-' }}</p>
                    </div>
                @else
                    <x-mv.alert>Sem caução registada para este contrato.</x-mv.alert>
                @endif
            </x-mv.section>
        </div>
    </div>
</x-app-layout>
