<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Visita {{ $visit->visit_number }}</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $visit->status->label() }}</h1>
                <p class="mt-1 text-sm text-ink-500">{{ $notice }}</p>
            </div>
            @if ($visit->isActive())
                <a href="{{ route('candidate.visits.reschedule', $visit) }}" class="mv-button-secondary">Reagendar</a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <section class="grid gap-4 md:grid-cols-2">
                <div class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Detalhes</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div><dt class="font-semibold text-ink-500">Data</dt><dd class="text-ink-900">{{ $visit->starts_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
                        <div><dt class="font-semibold text-ink-500">Local</dt><dd class="text-ink-900">{{ $visit->location ?? '—' }}</dd></div>
                        <div><dt class="font-semibold text-ink-500">Ponto de encontro</dt><dd class="text-ink-900">{{ $visit->meeting_point ?? '—' }}</dd></div>
                        <div><dt class="font-semibold text-ink-500">Contexto</dt><dd class="text-ink-900">{{ $visit->housingUnit?->title ?? $visit->contest?->title ?? $visit->application?->application_number ?? '—' }}</dd></div>
                    </dl>
                </div>
                <div class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Histórico</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($visit->statusHistories as $history)
                            <div class="border-l-2 border-civic-600 pl-4 text-sm">
                                <p class="font-semibold text-ink-900">{{ $history->to_status }}</p>
                                <p class="text-ink-500">{{ $history->changed_at?->format('d/m/Y H:i') }} · {{ $history->reason }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            @if ($visit->isActive())
                <form method="POST" action="{{ route('candidate.visits.cancel', $visit) }}" class="mv-surface space-y-4 p-6">
                    @csrf
                    <h2 class="text-lg font-semibold text-ink-900">Cancelar visita</h2>
                    <input type="hidden" name="cancellation_reason" value="candidate_unavailable">
                    <textarea name="cancellation_notes" rows="3" class="w-full rounded-md border-ink-300 text-sm" placeholder="Motivo opcional"></textarea>
                    <div class="flex justify-end"><button type="submit" class="mv-button-secondary">Cancelar visita</button></div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
