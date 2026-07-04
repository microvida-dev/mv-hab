<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Decisão administrativa"
            title="Propor admissão para classificação"
            description="Registe a proposta de admissão do processo para a fase de pontuação."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('backoffice.administrative-decisions.store-admission', $process) }}">
                @csrf
                <x-mv.section title="Proposta de admissão">
                    <textarea name="summary" rows="3" class="mv-input w-full text-sm" placeholder="Resumo da decisão"></textarea>
                    <textarea name="grounds" rows="5" class="mv-input mt-4 w-full text-sm" placeholder="Fundamentação obrigatória"></textarea>
                    <textarea name="legal_basis" rows="3" class="mv-input mt-4 w-full text-sm" placeholder="Base legal, quando validada"></textarea>

                    <div class="mt-4">
                        <x-mv.checkbox-card name="candidate_visible" label="Visível ao candidato" />
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="mv-button-primary">Registar decisão</button>
                    </div>
                </x-mv.section>
            </form>
        </div>
    </div>
</x-app-layout>
