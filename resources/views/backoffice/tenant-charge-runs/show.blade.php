<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Cobrança"
            :title="$tenantChargeRun->run_number"
            description="Detalhe dos itens gerados nesta execução operacional."
        />
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-4 px-4 py-6 sm:px-6 lg:px-8">
        <x-mv.alert>
            As cobranças automáticas registadas nesta plataforma correspondem à geração operacional de valores a cobrar e não implicam, por si só, movimento bancário externo sem integração devidamente configurada.
        </x-mv.alert>

        <x-mv.section title="Itens da cobrança">
            <div class="grid gap-3">
                @foreach ($tenantChargeRun->items as $item)
                    <div class="rounded-2xl border border-ink-100 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="font-semibold text-ink-900">{{ $item->tenant?->name }} · {{ number_format((float) $item->amount, 2, ',', '.') }} EUR</p>
                            <x-mv.badge>{{ $item->status }}</x-mv.badge>
                        </div>

                        <p class="mt-2 text-sm text-ink-500">{{ $item->message }}</p>
                    </div>
                @endforeach
            </div>
        </x-mv.section>
    </div>
</x-app-layout>
