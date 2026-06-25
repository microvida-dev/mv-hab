<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Visita {{ $visit->visit_number }}</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $visit->status->label() }}</h1>
            <p class="mt-1 text-sm text-ink-500">{{ $visit->candidate?->name }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            <section class="grid gap-4 md:grid-cols-2">
                <div class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Detalhes</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div><dt class="font-semibold text-ink-500">Data</dt><dd class="text-ink-900">{{ $visit->starts_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
                        <div><dt class="font-semibold text-ink-500">Local</dt><dd class="text-ink-900">{{ $visit->location ?? '—' }}</dd></div>
                        <div><dt class="font-semibold text-ink-500">Candidatura</dt><dd class="text-ink-900">{{ $visit->application?->application_number ?? '—' }}</dd></div>
                        <div><dt class="font-semibold text-ink-500">Habitação</dt><dd class="text-ink-900">{{ $visit->housingUnit?->title ?? '—' }}</dd></div>
                    </dl>
                </div>
                <div class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Ações</h2>
                    <div class="mt-4 grid gap-3">
                        <form method="POST" action="{{ route('backoffice.housing-visits.confirm', $visit) }}">@csrf<button class="mv-button-secondary w-full">Confirmar</button></form>
                        <form method="POST" action="{{ route('backoffice.housing-visits.complete', $visit) }}">@csrf<textarea name="staff_notes" rows="2" class="mb-2 w-full rounded-md border-ink-300 text-sm" placeholder="Notas" required></textarea><button class="mv-button-secondary w-full">Concluir</button></form>
                        <form method="POST" action="{{ route('backoffice.housing-visits.no-show', $visit) }}">@csrf<textarea name="staff_notes" rows="2" class="mb-2 w-full rounded-md border-ink-300 text-sm" placeholder="Nota de falta de comparência" required></textarea><button class="mv-button-secondary w-full">Falta de comparência</button></form>
                        <form method="POST" action="{{ route('backoffice.housing-visits.reject', $visit) }}">@csrf<textarea name="reason" rows="2" class="mb-2 w-full rounded-md border-ink-300 text-sm" placeholder="Motivo" required></textarea><button class="mv-button-secondary w-full">Recusar</button></form>
                        <form method="POST" action="{{ route('backoffice.housing-visits.cancel', $visit) }}">@csrf<input type="hidden" name="cancellation_reason" value="operational_reason"><textarea name="cancellation_notes" rows="2" class="mb-2 w-full rounded-md border-ink-300 text-sm" placeholder="Notas"></textarea><button class="mv-button-secondary w-full">Cancelar</button></form>
                    </div>
                </div>
            </section>

            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">Histórico</h2>
                <div class="mt-4 space-y-3">
                    @foreach ($visit->statusHistories as $history)
                        <div class="border-l-2 border-civic-600 pl-4 text-sm">
                            <p class="font-semibold text-ink-900">{{ $history->to_status }}</p>
                            <p class="text-ink-500">{{ $history->changed_at?->format('d/m/Y H:i') }} · {{ $history->reason }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
