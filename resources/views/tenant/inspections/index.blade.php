<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Área do Inquilino"
            title="Vistorias"
            description="Consulte vistorias visíveis na sua área de inquilino."
        />
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-4 px-4 py-6 sm:px-6 lg:px-8">
        <x-mv.alert>
            O agendamento de vistoria está sujeito à disponibilidade dos serviços municipais e à confirmação das partes envolvidas.
        </x-mv.alert>

        <x-mv.section padding="p-0" class="overflow-hidden">
            @forelse ($inspections as $inspection)
                <a class="block border-b border-ink-100 p-4 transition hover:bg-ink-50" href="{{ route('tenant.inspections.show', $inspection) }}">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="font-semibold text-ink-900">{{ $inspection->inspection_number }}</p>
                            <p class="mt-1 text-sm text-ink-500">{{ $inspection->scheduled_for?->format('d/m/Y H:i') }}</p>
                        </div>

                        <x-mv.badge>{{ $inspection->status?->label() }}</x-mv.badge>
                    </div>
                </a>
            @empty
                <x-mv.alert>Sem vistorias visíveis.</x-mv.alert>
            @endforelse
        </x-mv.section>

        {{ $inspections->links() }}
    </div>
</x-app-layout>
