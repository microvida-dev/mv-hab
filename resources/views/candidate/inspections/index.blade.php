<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Vistorias</h1></x-slot>
    <div class="mv-card"><p class="mb-4 text-sm text-ink-600">As vistorias associadas à sua habitação ficam disponíveis nesta área quando forem emitidas pelos serviços municipais.</p>@foreach ($inspections as $inspection)<a class="block border-t border-ink-100 py-3 first:border-t-0" href="{{ route('candidate.inspections.show', $inspection) }}"><span class="font-semibold">{{ $inspection->inspection_type->label() }}</span><span class="ml-2 text-sm text-ink-500">{{ $inspection->status->label() }}</span></a>@endforeach{{ $inspections->links() }}</div>
</x-app-layout>
