<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-mvhab-primary">Atribuição</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $contestHousingUnit->housingUnit?->code }}</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8">
        <x-flash-message />
        <div class="rounded-2xl border border-ink-100 bg-white p-6 text-sm">
            <dl class="grid gap-4 md:grid-cols-2">
                <div><dt class="text-ink-500">Concurso</dt><dd class="font-semibold">{{ $contestHousingUnit->contest?->title ?? 'Sem concurso' }}</dd></div>
                <div><dt class="text-ink-500">Estado</dt><dd class="font-semibold">{{ $contestHousingUnit->status->label() }}</dd></div>
                <div><dt class="text-ink-500">Tipologia</dt><dd>{{ $contestHousingUnit->typology }}</dd></div>
                <div><dt class="text-ink-500">Ocupação</dt><dd>{{ $contestHousingUnit->min_occupants ?? '-' }} a {{ $contestHousingUnit->max_occupants ?? '-' }}</dd></div>
            </dl>
            <div class="mt-6 flex flex-wrap gap-2">
                <a href="{{ route('backoffice.allocation.contest-housing-units.edit', $contestHousingUnit) }}" class="mv-button-secondary">Editar</a>
                <form method="POST" action="{{ route('backoffice.allocation.contest-housing-units.mark-available', $contestHousingUnit) }}">@csrf<button class="rounded-2xl border border-mvhab-support/40 px-3 py-2 font-semibold text-mvhab-primary">Disponível</button></form>
                <form method="POST" action="{{ route('backoffice.allocation.contest-housing-units.mark-unavailable', $contestHousingUnit) }}">@csrf<button class="rounded-2xl border border-red-200 px-3 py-2 font-semibold text-red-700">Indisponível</button></form>
            </div>
        </div>
    </div></div>
</x-app-layout>
