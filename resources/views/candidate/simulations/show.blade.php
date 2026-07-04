<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Simulação"
            :title="$simulationSession->result_status?->label() ?? 'Resultado'"
            :description="$notices['short']"
        >
            <x-slot name="actions">
                <a href="{{ route('candidate.simulations.index') }}" class="mv-button-secondary">Voltar</a>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[1fr_22rem] lg:px-8">
            <div class="space-y-6">
                <x-flash-message />
                <x-mv.section
                    title="Síntese"
                    :description="$simulationSession->result?->eligibility_summary"
                >
                    <div class="mt-5 grid gap-4 md:grid-cols-3">
                        <x-mv.stat-card
                            label="Tipologia"
                            :value="$simulationSession->result?->recommended_typology ?? 'A validar'"
                        />

                        <x-mv.stat-card
                            label="Renda máxima"
                            :value="$simulationSession->result?->estimated_rent_max ? number_format((float) $simulationSession->result->estimated_rent_max, 2, ',', ' ').' €' : 'A validar'"
                        />

                        <x-mv.stat-card
                            label="Completude"
                            :value="number_format((float) ($simulationSession->inputSnapshot?->completeness_score ?? 0), 0).'%'"
                        />
                    </div>
                </x-mv.section>

                <x-mv.section title="Impedimentos e avisos">
                    <div class="mt-4 space-y-3">
                        @forelse ($simulationSession->result?->impediments ?? [] as $impediment)
                            <div class="rounded-2xl border border-ink-100 bg-white p-4">
                                <p class="font-semibold text-ink-900">{{ $impediment->title }}</p>
                                <p class="mt-1 text-sm text-ink-600">{{ $impediment->message }}</p>
                            </div>
                        @empty
                            <x-mv.alert tone="success">Sem impedimentos detetados.</x-mv.alert>
                        @endforelse
                    </div>
                </x-mv.section>
            </div>

            <aside class="space-y-4">
                <x-mv.section title="Próximas ações">
                    <form method="POST" action="{{ route('candidate.simulations.save', $simulationSession) }}" class="mt-4">
                        @csrf
                        <input type="hidden" name="confirm_save" value="1">
                        <button class="mv-button-secondary w-full justify-center">Guardar</button>
                    </form>
                    <form method="POST" action="{{ route('candidate.simulations.prefill', $simulationSession) }}" class="mt-3">
                        @csrf
                        <input type="hidden" name="confirm_indicative_result" value="1">
                        <button class="mv-button-primary w-full justify-center">Pré-preencher candidatura</button>
                    </form>
                </x-mv.section>
                <x-mv.section title="Concursos">
                    <div class="mt-4 space-y-3">
                        @forelse ($simulationSession->result?->recommendedContests ?? [] as $recommendation)
                            <a href="{{ $recommendation->cta_url }}" class="block rounded-2xl border border-ink-100 p-3 text-sm font-semibold text-mvhab-primary">{{ $recommendation->contest->title }}</a>
                        @empty
                            <x-mv.alert>Sem recomendações.</x-mv.alert>
                        @endforelse
                    </div>
                </x-mv.section>
            </aside>
        </div>
    </div>
</x-app-layout>
