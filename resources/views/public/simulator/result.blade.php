<x-public-layout title="Resultado da simulação" description="Resultado indicativo do simulador de candidatura.">
    <section class="bg-ink-50 py-10">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <p class="text-sm font-semibold text-civic-700">Resultado indicativo</p>
            <h1 class="mt-2 text-3xl font-semibold text-ink-900">{{ $session->result?->result_status?->label() ?? 'Simulação' }}</h1>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-ink-600">{{ $notices['public'] }}</p>
        </div>
    </section>

    <section class="py-8">
        <div class="mx-auto grid max-w-6xl gap-6 px-4 sm:px-6 lg:grid-cols-[1fr_22rem] lg:px-8">
            <div class="space-y-6">
                <div class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Síntese</h2>
                    <p class="mt-2 text-sm leading-6 text-ink-600">{{ $session->result?->eligibility_summary }}</p>
                    <dl class="mt-5 grid gap-4 sm:grid-cols-3">
                        <div>
                            <dt class="text-xs font-semibold uppercase text-ink-500">Tipologia</dt>
                            <dd class="mt-1 font-semibold text-ink-900">{{ $session->result?->recommended_typology ?? 'A validar' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase text-ink-500">Renda estimada</dt>
                            <dd class="mt-1 font-semibold text-ink-900">
                                @if ($session->result?->estimated_rent_max)
                                    até {{ number_format((float) $session->result->estimated_rent_max, 2, ',', ' ') }} €
                                @else
                                    A validar
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase text-ink-500">Dados mínimos</dt>
                            <dd class="mt-1 font-semibold text-ink-900">{{ number_format((float) ($session->inputSnapshot?->completeness_score ?? 0), 0) }}%</dd>
                        </div>
                    </dl>
                </div>

                <div class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Alertas</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($session->result?->impediments ?? [] as $impediment)
                            <div class="rounded-md border border-ink-100 p-4">
                                <p class="font-semibold text-ink-900">{{ $impediment->title }}</p>
                                <p class="mt-1 text-sm text-ink-600">{{ $impediment->message }}</p>
                                @if ($impediment->recommendation)
                                    <p class="mt-2 text-xs font-medium text-civic-700">{{ $impediment->recommendation }}</p>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-ink-500">Não foram encontrados impedimentos com os dados indicados.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <aside class="space-y-4">
                <div class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Concursos recomendados</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($session->result?->recommendedContests ?? [] as $recommendation)
                            <a href="{{ $recommendation->cta_url }}" class="block rounded-md border border-ink-100 p-4 hover:border-civic-300">
                                <p class="font-semibold text-ink-900">{{ $recommendation->contest->title }}</p>
                                <p class="mt-1 text-xs text-ink-500">{{ number_format((float) $recommendation->match_score, 0) }}% compatível</p>
                            </a>
                        @empty
                            <p class="text-sm text-ink-500">Sem concursos recomendados.</p>
                        @endforelse
                    </div>
                    <a href="{{ route('register') }}" class="mv-button-primary mt-5 w-full justify-center">Criar conta</a>
                </div>
            </aside>
        </div>
    </section>
</x-public-layout>
