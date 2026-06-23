<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Vistorias</h1></x-slot>
    <div class="mx-auto grid max-w-7xl gap-4 px-4 py-6 sm:px-6 lg:px-8">
        <div class="mv-card"><p class="text-sm text-ink-600">O agendamento de vistoria está sujeito à disponibilidade dos serviços municipais e à confirmação das partes envolvidas.</p></div>
        @forelse ($inspections as $inspection)
            <a class="mv-card block" href="{{ route('tenant.inspections.show', $inspection) }}">
                <p class="font-semibold">{{ $inspection->inspection_number }}</p>
                <p class="text-sm text-ink-500">{{ $inspection->status?->label() }} · {{ $inspection->scheduled_for?->format('d/m/Y H:i') }}</p>
            </a>
        @empty
            <div class="mv-card"><p class="text-sm text-ink-600">Sem vistorias visíveis.</p></div>
        @endforelse
        {{ $inspections->links() }}
    </div>
</x-app-layout>
