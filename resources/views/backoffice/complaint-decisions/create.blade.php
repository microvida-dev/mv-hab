<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Reclamações"
            title="Criar decisão"
            description="Registe a proposta de decisão para a reclamação, mantendo a aprovação como passo explícito."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('backoffice.complaint-decisions.store', $complaint) }}">
                @csrf
                <x-mv.section title="Dados da decisão">
                    <div>
                        <x-input-label for="decision_result" value="Resultado" />
                        <select id="decision_result" name="decision_result" class="mv-input mt-1 w-full text-sm">
                            @foreach ($results as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-5">
                        <x-input-label for="summary" value="Resumo" />
                        <textarea id="summary" name="summary" class="mv-input mt-1 w-full text-sm" required></textarea>
                    </div>

                    <div class="mt-5">
                        <x-input-label for="grounds" value="Fundamentos" />
                        <textarea id="grounds" name="grounds" class="mv-input mt-1 w-full text-sm" required></textarea>
                    </div>

                    <div class="mt-5">
                        <x-input-label for="legal_basis" value="Base legal" />
                        <textarea id="legal_basis" name="legal_basis" class="mv-input mt-1 w-full text-sm"></textarea>
                    </div>

                    <div class="mt-5 grid gap-3 md:grid-cols-2">
                        <input type="hidden" name="requires_list_update" value="0">
                        <x-mv.checkbox-card name="requires_list_update" label="Exige impacto na lista definitiva" />

                        <input type="hidden" name="candidate_visible" value="0">
                        <x-mv.checkbox-card name="candidate_visible" label="Visível ao candidato após aprovação" />
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="mv-button-primary">Criar decisão</button>
                    </div>
                </x-mv.section>
            </form>
        </div>
    </div>
</x-app-layout>
