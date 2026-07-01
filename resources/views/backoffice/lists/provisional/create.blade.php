<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-mvhab-primary">Listas</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Gerar lista provisória</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8"><form method="POST" action="{{ route('backoffice.lists.provisional.store') }}" class="mv-surface space-y-5 p-6">@csrf
        <div><x-input-label for="ranking_snapshot_id" value="Snapshot de ranking" /><select id="ranking_snapshot_id" name="ranking_snapshot_id" class="mv-input mt-1 w-full">@foreach($snapshots as $snapshot)<option value="{{ $snapshot->id }}">#{{ $snapshot->snapshot_number }} · {{ $snapshot->contest?->title ?? $snapshot->program?->name }} · {{ $snapshot->status->label() }}</option>@endforeach</select><x-input-error :messages="$errors->get('ranking_snapshot_id')" /></div>
        <div><x-input-label for="title" value="Título" /><x-text-input id="title" name="title" class="mt-1 w-full" value="{{ old('title') }}" required /><x-input-error :messages="$errors->get('title')" /></div>
        <div><x-input-label for="description" value="Descrição" /><textarea id="description" name="description" class="mv-input mt-1 w-full">{{ old('description') }}</textarea></div>
        <div class="grid gap-4 md:grid-cols-2"><div><x-input-label for="complaint_period_starts_at" value="Início reclamações" /><x-text-input type="datetime-local" id="complaint_period_starts_at" name="complaint_period_starts_at" class="mt-1 w-full" /></div><div><x-input-label for="complaint_period_ends_at" value="Fim reclamações" /><x-text-input type="datetime-local" id="complaint_period_ends_at" name="complaint_period_ends_at" class="mt-1 w-full" /></div></div>
        <div><x-input-label for="anonymization_mode" value="Anonimização" /><select id="anonymization_mode" name="anonymization_mode" class="mv-input mt-1 w-full">@foreach($anonymizationModes as $value => $label)<option value="{{ $value }}" @selected($value === 'public_identifier_only')>{{ $label }}</option>@endforeach</select></div>
        <label class="flex items-center gap-2 text-sm text-ink-700"><input type="hidden" name="public_visibility" value="0"><input type="checkbox" name="public_visibility" value="1" class="rounded border-ink-300"> Visível no portal público anonimizado</label>
        <div><x-input-label for="legal_basis" value="Base legal" /><textarea id="legal_basis" name="legal_basis" class="mv-input mt-1 w-full">{{ old('legal_basis') }}</textarea></div>
        <div class="flex justify-end"><x-primary-button>Gerar</x-primary-button></div>
    </form></div></div>
</x-app-layout>

