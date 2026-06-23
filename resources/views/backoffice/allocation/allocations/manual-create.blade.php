<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-civic-700">Atribuição</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Exceção manual</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-3xl space-y-4 px-4 sm:px-6 lg:px-8"><x-flash-message /><form method="POST" action="{{ route('backoffice.allocation.allocations.manual-store') }}" class="space-y-4 rounded-md border border-ink-100 bg-white p-6">@csrf
        <label class="block text-sm font-medium text-ink-700">Execução<select name="allocation_run_id" class="mt-1 w-full rounded-md border-ink-200">@foreach($runs as $run)<option value="{{ $run->id }}">{{ $run->run_number }} · {{ $run->contest?->title }}</option>@endforeach</select></label>
        <label class="block text-sm font-medium text-ink-700">Entrada da lista<select name="definitive_list_entry_id" class="mt-1 w-full rounded-md border-ink-200">@foreach($entries as $entry)<option value="{{ $entry->id }}">{{ $entry->rank_position }} · {{ $entry->candidate?->name }}</option>@endforeach</select></label>
        <label class="block text-sm font-medium text-ink-700">Habitação<select name="contest_housing_unit_id" class="mt-1 w-full rounded-md border-ink-200">@foreach($units as $unit)<option value="{{ $unit->id }}">{{ $unit->housingUnit?->code }} · {{ $unit->contest?->title }}</option>@endforeach</select></label>
        <label class="block text-sm font-medium text-ink-700">Justificação<textarea name="manual_justification" class="mt-1 w-full rounded-md border-ink-200"></textarea></label>
        <div class="flex justify-end"><button class="rounded-md bg-civic-700 px-4 py-2 text-sm font-semibold text-white">Criar atribuição</button></div>
    </form></div></div>
</x-app-layout>
