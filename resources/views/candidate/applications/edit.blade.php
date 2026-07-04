<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Candidatura em rascunho"
            title="Editar notas"
            :description="$application->contest->title"
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('candidate.applications.update', $application) }}" class="space-y-6">
                @csrf
                @method('PATCH')

                <x-mv.section title="Notas da candidatura">
                    <x-input-label for="candidate_notes" value="Notas opcionais" />
                    <textarea id="candidate_notes" name="candidate_notes" rows="6" class="mv-input mt-1 block w-full">{{ old('candidate_notes', $application->candidate_notes) }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('candidate_notes')" />
                </x-mv.section>

                <div class="flex flex-wrap justify-end gap-3">
                    <a href="{{ route('candidate.applications.show', $application) }}" class="mv-button-secondary">Cancelar</a>
                    <button type="submit" class="mv-button-primary">Guardar alterações</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
