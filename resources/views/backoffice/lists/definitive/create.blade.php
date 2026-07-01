<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-mvhab-primary">Listas</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Gerar lista definitiva</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8"><form method="POST" action="{{ route('backoffice.lists.definitive.store') }}" class="mv-surface space-y-5 p-6">@csrf
        <div><x-input-label for="provisional_list_id" value="Lista provisória" /><select id="provisional_list_id" name="provisional_list_id" class="mv-input mt-1 w-full">@foreach($provisionalLists as $list)<option value="{{ $list->id }}">{{ $list->list_number }} · {{ $list->contest?->title ?? $list->program?->name }}</option>@endforeach</select></div>
        <div><x-input-label for="title" value="Título" /><x-text-input id="title" name="title" class="mt-1 w-full" required /></div>
        <div><x-input-label for="description" value="Descrição" /><textarea id="description" name="description" class="mv-input mt-1 w-full"></textarea></div>
        <div><x-input-label for="anonymization_mode" value="Anonimização" /><select id="anonymization_mode" name="anonymization_mode" class="mv-input mt-1 w-full">@foreach($anonymizationModes as $value => $label)<option value="{{ $value }}" @selected($value === 'public_identifier_only')>{{ $label }}</option>@endforeach</select></div>
        <label class="flex items-center gap-2 text-sm text-ink-700"><input type="hidden" name="public_visibility" value="0"><input type="checkbox" name="public_visibility" value="1" class="rounded border-ink-300"> Visível no portal público anonimizado</label>
        <div class="flex justify-end"><x-primary-button>Gerar</x-primary-button></div>
    </form></div></div>
</x-app-layout>

