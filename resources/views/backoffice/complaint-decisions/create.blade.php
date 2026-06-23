<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-civic-700">Reclamações</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Criar decisão</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8"><form method="POST" action="{{ route('backoffice.complaint-decisions.store', $complaint) }}" class="space-y-5 rounded-md border border-ink-100 bg-white p-6">@csrf
        <div><x-input-label for="decision_result" value="Resultado" /><select id="decision_result" name="decision_result" class="mt-1 w-full rounded-md border-ink-200">@foreach($results as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
        <div><x-input-label for="summary" value="Resumo" /><textarea id="summary" name="summary" class="mt-1 w-full rounded-md border-ink-200" required></textarea></div>
        <div><x-input-label for="grounds" value="Fundamentos" /><textarea id="grounds" name="grounds" class="mt-1 w-full rounded-md border-ink-200" required></textarea></div>
        <div><x-input-label for="legal_basis" value="Base legal" /><textarea id="legal_basis" name="legal_basis" class="mt-1 w-full rounded-md border-ink-200"></textarea></div>
        <label class="flex items-center gap-2 text-sm"><input type="hidden" name="requires_list_update" value="0"><input type="checkbox" name="requires_list_update" value="1" class="rounded border-ink-300"> Exige impacto na lista definitiva</label>
        <label class="flex items-center gap-2 text-sm"><input type="hidden" name="candidate_visible" value="0"><input type="checkbox" name="candidate_visible" value="1" class="rounded border-ink-300"> Visível ao candidato após aprovação</label>
        <div class="flex justify-end"><x-primary-button>Criar decisão</x-primary-button></div>
    </form></div></div>
</x-app-layout>

