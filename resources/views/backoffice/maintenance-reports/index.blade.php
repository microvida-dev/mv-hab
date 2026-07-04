<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Manutenção"
            title="Relatórios de manutenção"
            description="Pedidos em aberto para acompanhamento e relatório operacional."
        />
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-4 px-4 py-6 sm:px-6 lg:px-8">
        <x-mv.alert>
            Os pedidos de manutenção serão analisados pelos serviços municipais, podendo ser solicitada informação adicional ou agendada vistoria/intervenção técnica.
        </x-mv.alert>

        @forelse ($requests as $request)
            <x-mv.section>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-semibold text-ink-900">{{ $request->request_number }} · {{ $request->title }}</p>
                        <p class="mt-1 text-sm text-ink-500">{{ $request->housingUnit?->address }}</p>
                    </div>

                    <x-mv.badge>{{ $request->status?->label() }}</x-mv.badge>
                </div>
            </x-mv.section>
        @empty
            <x-mv.alert>Sem pedidos em aberto para relatório.</x-mv.alert>
        @endforelse
    </div>
</x-app-layout>
