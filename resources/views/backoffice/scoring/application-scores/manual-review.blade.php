<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-civic-700">Avaliação manual</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $score->application?->application_number }}</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-4xl space-y-4 px-4 sm:px-6 lg:px-8">
        @forelse ($pendingDetails as $detail)
            <div class="rounded-md border border-ink-100 bg-white p-6"><h2 class="font-semibold text-ink-900">{{ $detail->name }}</h2><p class="mt-1 text-sm text-ink-600">{{ $detail->message }}</p><form method="POST" action="{{ route('backoffice.scoring.application-scores.manual-review.update', $score) }}" class="mt-5 grid gap-4 md:grid-cols-[1fr_2fr_auto]">@csrf @method('PUT')<input type="hidden" name="application_score_detail_id" value="{{ $detail->id }}"><div><x-input-label for="manual_points_{{ $detail->id }}" value="Pontos" /><x-text-input id="manual_points_{{ $detail->id }}" name="manual_points" type="number" step="0.01" max="{{ $detail->max_points }}" class="mt-1 block w-full" required /></div><div><x-input-label for="manual_notes_{{ $detail->id }}" value="Notas" /><x-text-input id="manual_notes_{{ $detail->id }}" name="manual_notes" class="mt-1 block w-full" /></div><div class="pt-6"><button class="rounded-md bg-civic-700 px-4 py-2 text-sm font-semibold text-white">Guardar</button></div></form></div>
        @empty
            <div class="rounded-md border border-ink-100 bg-white p-6 text-sm text-ink-600">Não existem critérios manuais pendentes.</div>
        @endforelse
    </div></div>
</x-app-layout>
