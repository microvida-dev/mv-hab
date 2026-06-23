<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Simulação</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $simulationSession->result_status?->label() ?? 'Resultado' }}</h1>
                <p class="mt-1 text-sm text-ink-500">{{ $notices['short'] }}</p>
            </div>
            <a href="{{ route('candidate.simulations.index') }}" class="mv-button-secondary">Voltar</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[1fr_22rem] lg:px-8">
            <div class="space-y-6">
                <x-flash-message />
                <section class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Síntese</h2>
                    <p class="mt-2 text-sm text-ink-600">{{ $simulationSession->result?->eligibility_summary }}</p>
                    <div class="mt-5 grid gap-4 md:grid-cols-3">
                        <div><p class="text-xs font-semibold uppercase text-ink-500">Tipologia</p><p class="font-semibold text-ink-900">{{ $simulationSession->result?->recommended_typology ?? 'A validar' }}</p></div>
                        <div><p class="text-xs font-semibold uppercase text-ink-500">Renda máxima</p><p class="font-semibold text-ink-900">{{ $simulationSession->result?->estimated_rent_max ? number_format((float) $simulationSession->result->estimated_rent_max, 2, ',', ' ').' €' : 'A validar' }}</p></div>
                        <div><p class="text-xs font-semibold uppercase text-ink-500">Completude</p><p class="font-semibold text-ink-900">{{ number_format((float) ($simulationSession->inputSnapshot?->completeness_score ?? 0), 0) }}%</p></div>
                    </div>
                </section>

                <section class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Impedimentos e avisos</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($simulationSession->result?->impediments ?? [] as $impediment)
                            <div class="rounded-md border border-ink-100 p-4">
                                <p class="font-semibold text-ink-900">{{ $impediment->title }}</p>
                                <p class="mt-1 text-sm text-ink-600">{{ $impediment->message }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-ink-500">Sem impedimentos detetados.</p>
                        @endforelse
                    </div>
                </section>
            </div>

            <aside class="space-y-4">
                <section class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Próximas ações</h2>
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
                </section>
                <section class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Concursos</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($simulationSession->result?->recommendedContests ?? [] as $recommendation)
                            <a href="{{ $recommendation->cta_url }}" class="block rounded-md border border-ink-100 p-3 text-sm font-semibold text-civic-700">{{ $recommendation->contest->title }}</a>
                        @empty
                            <p class="text-sm text-ink-500">Sem recomendações.</p>
                        @endforelse
                    </div>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>
