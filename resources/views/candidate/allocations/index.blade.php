<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-civic-700">Área do candidato</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Atribuições</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8"><x-flash-message /><div class="rounded-md border border-ink-100 bg-white">@forelse($allocations as $allocation)<a href="{{ route('candidate.allocations.show', $allocation) }}" class="block border-b border-ink-100 p-4"><span class="font-semibold">{{ $allocation->housingUnit?->code }}</span><span class="ml-2 text-sm text-ink-500">{{ $allocation->status->label() }}</span></a>@empty<p class="p-6 text-sm text-ink-500">Sem atribuições.</p>@endforelse</div>{{ $allocations->links() }}</div></div>
</x-app-layout>
