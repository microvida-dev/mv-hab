<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-mvhab-primary">Sorteios</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Presenças</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
        <x-flash-message />
        <div class="grid gap-3 md:grid-cols-4">@foreach($summary as $label => $value)<div class="rounded-2xl border border-ink-100 bg-mvhab-card p-4"><p class="text-sm text-ink-500">{{ $label }}</p><p class="text-2xl font-semibold">{{ $value }}</p></div>@endforeach</div>
        <div class="mv-surface overflow-hidden"><table class="min-w-full divide-y divide-ink-100 text-sm"><thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500"><tr><th class="px-4 py-3">Participante</th><th class="px-4 py-3">Candidato</th><th class="px-4 py-3">Registar</th></tr></thead><tbody class="divide-y divide-ink-100">
            @foreach($lotteryDraw->participants as $participant)
                <tr><td class="px-4 py-3">{{ $participant->participant_number }}</td><td class="px-4 py-3">{{ $participant->candidate?->name }}</td><td class="px-4 py-3"><form method="POST" action="{{ route('backoffice.lottery-draws.attendance.store', $lotteryDraw) }}" class="flex gap-2">@csrf<input type="hidden" name="application_id" value="{{ $participant->application_id }}"><input type="hidden" name="user_id" value="{{ $participant->user_id }}"><input type="hidden" name="lottery_participant_id" value="{{ $participant->id }}"><select name="status" class="rounded-2xl border-ink-200"><option value="present">Presente</option><option value="absent">Ausente</option><option value="justified">Justificada</option></select><button class="rounded-2xl border border-ink-200 px-3 py-2 text-sm font-semibold">Guardar</button></form></td></tr>
            @endforeach
        </tbody></table></div>
    </div></div>
</x-app-layout>
