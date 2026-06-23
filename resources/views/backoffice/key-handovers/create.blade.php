<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-civic-700">Entrega de chaves</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Agendar entrega</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8"><form method="POST" action="{{ route('backoffice.key-handovers.store') }}" class="space-y-4 rounded-md border border-ink-100 bg-white p-6">@csrf
        <select name="winner_registration_id" class="w-full rounded-md border-ink-200">@foreach($winners as $winner)<option value="{{ $winner->id }}">{{ $winner->candidate?->name }} — {{ $winner->housingUnit?->code }}</option>@endforeach</select>
        <input name="scheduled_for" type="datetime-local" class="w-full rounded-md border-ink-200"><input name="location" placeholder="Local" class="w-full rounded-md border-ink-200"><textarea name="instructions" rows="4" class="w-full rounded-md border-ink-200">A entrega de chaves só deve ocorrer após validação dos requisitos administrativos, contratuais e documentais aplicáveis.</textarea>
        <button class="rounded-md bg-civic-700 px-4 py-2 text-sm font-semibold text-white">Agendar</button>
    </form></div></div>
</x-app-layout>
