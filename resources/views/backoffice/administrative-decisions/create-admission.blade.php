<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Decisão administrativa</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Propor admissão para classificação</h1>
        </div>
    </x-slot>

    <div class="py-8"><div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('backoffice.administrative-decisions.store-admission', $process) }}" class="mv-surface space-y-4 p-6">
            @csrf
            <textarea name="summary" rows="3" class="w-full rounded-md border-ink-300 text-sm" placeholder="Resumo da decisão"></textarea>
            <textarea name="grounds" rows="5" class="w-full rounded-md border-ink-300 text-sm" placeholder="Fundamentação obrigatória"></textarea>
            <textarea name="legal_basis" rows="3" class="w-full rounded-md border-ink-300 text-sm" placeholder="Base legal, quando validada"></textarea>
            <label class="flex items-center gap-2 text-sm text-ink-700"><input type="checkbox" name="candidate_visible" value="1" class="rounded border-ink-300">Visível ao candidato</label>
            <button class="mv-button-primary">Registar decisão</button>
        </form>
    </div></div>
</x-app-layout>
