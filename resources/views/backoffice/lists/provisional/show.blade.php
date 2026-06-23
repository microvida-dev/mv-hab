<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-civic-700">Lista provisória</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $provisionalList->title }}</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="rounded-md border border-ink-100 bg-white p-6"><dl class="grid gap-4 md:grid-cols-4 text-sm"><div><dt class="text-ink-500">Número</dt><dd class="font-semibold">{{ $provisionalList->list_number }}</dd></div><div><dt class="text-ink-500">Estado</dt><dd>{{ $provisionalList->status->label() }}</dd></div><div><dt class="text-ink-500">Snapshot</dt><dd>#{{ $provisionalList->rankingSnapshot?->snapshot_number }}</dd></div><div><dt class="text-ink-500">Reclamações</dt><dd>{{ $provisionalList->complaint_period_starts_at?->format('d/m/Y H:i') }} · {{ $provisionalList->complaint_period_ends_at?->format('d/m/Y H:i') }}</dd></div></dl></div>
        <div class="flex flex-wrap gap-2">
            <form method="POST" action="{{ route('backoffice.lists.provisional.review', $provisionalList) }}">@csrf<x-secondary-button>Rever</x-secondary-button></form>
            <form method="POST" action="{{ route('backoffice.lists.provisional.approve', $provisionalList) }}">@csrf<x-secondary-button>Aprovar</x-secondary-button></form>
            <form method="POST" action="{{ route('backoffice.lists.provisional.publish', $provisionalList) }}">@csrf<x-primary-button>Publicar</x-primary-button></form>
            <form method="POST" action="{{ route('backoffice.lists.provisional.open-complaint-period', $provisionalList) }}">@csrf<x-secondary-button>Abrir reclamações</x-secondary-button></form>
            <form method="POST" action="{{ route('backoffice.lists.provisional.close-complaint-period', $provisionalList) }}">@csrf<x-secondary-button>Fechar reclamações</x-secondary-button></form>
        </div>
        <div class="rounded-md border border-ink-100 bg-white"><table class="min-w-full divide-y divide-ink-100 text-sm"><thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500"><tr><th class="px-4 py-3">Posição</th><th class="px-4 py-3">Identificador</th><th class="px-4 py-3">Candidato</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3">Pontuação</th></tr></thead><tbody class="divide-y divide-ink-100">@foreach($provisionalList->entries as $entry)<tr><td class="px-4 py-3">{{ $entry->rank_position }}</td><td class="px-4 py-3 font-semibold">{{ $entry->public_identifier }}</td><td class="px-4 py-3">{{ $entry->application->user?->name }}</td><td class="px-4 py-3">{{ $entry->status->label() }}</td><td class="px-4 py-3">{{ $entry->total_score }}</td></tr>@endforeach</tbody></table></div>
        <p class="rounded-md bg-amber-50 p-4 text-sm text-amber-900">Ao publicar esta lista, os candidatos poderão consultar os resultados e, se aplicável, apresentar reclamação dentro do prazo configurado. Confirme que a lista foi revista, aprovada e que a anonimização está correta.</p>
    </div></div>
</x-app-layout>

