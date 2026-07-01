<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-mvhab-primary">Audiências</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Criar audiência</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8"><form method="POST" action="{{ route('backoffice.hearings.store') }}" class="mv-surface space-y-5 p-6">@csrf
        <div><x-input-label for="application_id" value="Candidatura" /><select id="application_id" name="application_id" class="mv-input mt-1 w-full">@foreach($applications as $application)<option value="{{ $application->id }}">{{ $application->application_number ?? $application->public_id }} · {{ $application->user?->name }}</option>@endforeach</select></div>
        <div><x-input-label for="hearing_type" value="Tipo" /><select id="hearing_type" name="hearing_type" class="mv-input mt-1 w-full">@foreach($types as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
        <div><x-input-label for="subject" value="Assunto" /><x-text-input id="subject" name="subject" class="mt-1 w-full" required /></div>
        <div><x-input-label for="message" value="Mensagem" /><textarea id="message" name="message" class="mv-input mt-1 w-full" required></textarea></div>
        <div><x-input-label for="grounds" value="Fundamentos" /><textarea id="grounds" name="grounds" class="mv-input mt-1 w-full" required></textarea></div>
        <div><x-input-label for="deadline_at" value="Prazo" /><x-text-input type="datetime-local" id="deadline_at" name="deadline_at" class="mt-1 w-full" required /></div>
        <label class="flex items-center gap-2 text-sm"><input type="hidden" name="candidate_visible" value="0"><input type="checkbox" name="candidate_visible" value="1" class="rounded border-ink-300"> Visível após emissão</label>
        <div class="flex justify-end"><x-primary-button>Criar</x-primary-button></div>
    </form></div></div>
</x-app-layout>

