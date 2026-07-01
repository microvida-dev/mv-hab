<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-mvhab-primary">Candidatura em rascunho</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Editar notas</h1>
            <p class="mt-1 text-sm text-ink-500">{{ $application->contest->title }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('candidate.applications.update', $application) }}" class="mv-surface space-y-6 p-6">
                @csrf
                @method('PATCH')

                <div>
                    <x-input-label for="candidate_notes" value="Notas opcionais" />
                    <textarea id="candidate_notes" name="candidate_notes" rows="6" class="mv-input mt-1 block w-full">{{ old('candidate_notes', $application->candidate_notes) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('candidate_notes')" />
                </div>

                <div class="flex flex-wrap justify-end gap-3">
                    <a href="{{ route('candidate.applications.show', $application) }}" class="mv-button-secondary">Cancelar</a>
                    <button type="submit" class="mv-button-primary">Guardar alterações</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
