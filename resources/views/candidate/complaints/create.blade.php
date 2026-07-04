<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Reclamações"
            title="Apresentar reclamação"
            description="Registe os fundamentos da reclamação dentro do prazo indicado para o resultado provisório."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-mv.alert tone="info">
                Pode apresentar reclamação relativamente ao resultado provisório da sua candidatura dentro do prazo indicado. A reclamação deve indicar os fundamentos e, se aplicável, juntar documentos ou informação complementar.
            </x-mv.alert>

            <form method="POST" action="{{ route('candidate.complaints.store') }}" class="space-y-6">
                @csrf
                @php($selectedEntry = $entries->firstWhere('id', (int) request('provisional_list_entry_id')) ?? $entries->first())

                <x-mv.section title="Resultado contestado">
                    <div>
                        <x-input-label for="provisional_list_entry_id" value="Resultado" />
                        <select id="provisional_list_entry_id" name="provisional_list_entry_id" class="mv-input mt-1 w-full">
                            @foreach ($entries as $entry)
                                <option value="{{ $entry->id }}" @selected((string) request('provisional_list_entry_id') === (string) $entry->id)>
                                    {{ $entry->public_identifier }} · {{ $entry->provisionalList->contest?->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <input type="hidden" name="provisional_list_id" value="{{ old('provisional_list_id', $selectedEntry?->provisional_list_id) }}">
                    <input type="hidden" name="application_id" value="{{ old('application_id', $selectedEntry?->application_id) }}">
                </x-mv.section>

                <x-mv.section title="Fundamentos">
                    <div class="space-y-5">
                        <div>
                            <x-input-label for="subject" value="Assunto" />
                            <x-text-input id="subject" name="subject" class="mt-1 w-full" required />
                        </div>

                        <div>
                            <x-input-label for="grounds" value="Fundamentos" />
                            <textarea id="grounds" name="grounds" class="mv-input mt-1 w-full" required></textarea>
                        </div>

                        <div>
                            <x-input-label for="requested_outcome" value="Resultado pretendido" />
                            <textarea id="requested_outcome" name="requested_outcome" class="mv-input mt-1 w-full"></textarea>
                        </div>
                    </div>
                </x-mv.section>

                <div class="flex justify-end">
                    <button class="mv-button-primary">Guardar rascunho</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
