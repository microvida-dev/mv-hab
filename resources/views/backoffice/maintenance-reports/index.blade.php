<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Relatórios de manutenção</h1></x-slot>
    <div class="mx-auto grid max-w-7xl gap-4 px-4 py-6 sm:px-6 lg:px-8">
        <div class="mv-card"><p class="text-sm text-ink-600">Os pedidos de manutenção serão analisados pelos serviços municipais, podendo ser solicitada informação adicional ou agendada vistoria/intervenção técnica.</p></div>
        @forelse ($requests as $request)
            <div class="mv-card">
                <p class="font-semibold">{{ $request->request_number }} · {{ $request->title }}</p>
                <p class="text-sm text-ink-500">{{ $request->status?->label() }} · {{ $request->housingUnit?->address }}</p>
            </div>
        @empty
            <div class="mv-card"><p class="text-sm text-ink-600">Sem pedidos em aberto para relatório.</p></div>
        @endforelse
    </div>
</x-app-layout>
