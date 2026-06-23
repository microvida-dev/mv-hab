<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Simulação {{ $simulationSession->uuid }}</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $simulationSession->result_status?->label() ?? 'Detalhe' }}</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">Entrada</h2>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between"><dt>Utilizador</dt><dd>{{ $simulationSession->user?->email ?? 'Anónimo' }}</dd></div>
                    <div class="flex justify-between"><dt>Escopo</dt><dd>{{ $simulationSession->scope->label() }}</dd></div>
                    <div class="flex justify-between"><dt>Completude</dt><dd>{{ $simulationSession->inputSnapshot?->completeness_score }}%</dd></div>
                </dl>
            </section>
            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">Resultado</h2>
                <p class="mt-2 text-sm text-ink-600">{{ $simulationSession->result?->eligibility_summary }}</p>
                <p class="mt-4 text-sm text-ink-600">Impedimentos: {{ $simulationSession->result?->impediments_count ?? 0 }}</p>
                <p class="mt-1 text-sm text-ink-600">Concursos recomendados: {{ $simulationSession->result?->recommended_contests_count ?? 0 }}</p>
            </section>
        </div>
    </div>
</x-app-layout>
