<x-public-layout title="Resultado da simulação" description="Resultado indicativo do simulador de candidatura.">
    <section class="mv-section border-b border-ink-100 bg-white">
        <div class="mv-container">
            <p class="mv-caption">Resultado indicativo</p>

            <h1 class="mv-heading mt-3">
                {{ $session->result?->result_status?->label() ?? 'Simulação' }}
            </h1>

            <p class="mv-description mt-6 max-w-3xl">
                {{ $notices['public'] }}
            </p>
        </div>
    </section>

    <section class="mv-section bg-mvhab-surface">
        <div class="mv-container grid gap-8 lg:grid-cols-[minmax(0,1fr)_24rem]">
            <div class="space-y-6">
                <section class="mv-card p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-mvhab-surface text-mvhab-primary">
                            <x-mv-icon name="simulator" size="lg" />
                        </div>

                        <div>
                            <h2 class="mv-card-title">Síntese</h2>
                            <p class="mv-section-description mt-2">
                                {{ $session->result?->eligibility_summary }}
                            </p>
                        </div>
                    </div>

                    <dl class="mt-6 grid gap-4 sm:grid-cols-3">
                        <div class="mv-card-muted p-4">
                            <dt class="mv-data-label">Tipologia</dt>
                            <dd class="mv-data-value">
                                {{ $session->result?->recommended_typology ?? 'A validar' }}
                            </dd>
                        </div>

                        <div class="mv-card-muted p-4">
                            <dt class="mv-data-label">Renda estimada</dt>
                            <dd class="mv-data-value">
                                @if ($session->result?->estimated_rent_max)
                                    até {{ number_format((float) $session->result->estimated_rent_max, 2, ',', ' ') }} €
                                @else
                                    A validar
                                @endif
                            </dd>
                        </div>

                        <div class="mv-card-muted p-4">
                            <dt class="mv-data-label">Dados mínimos</dt>
                            <dd class="mv-data-value">
                                {{ number_format((float) ($session->inputSnapshot?->completeness_score ?? 0), 0) }}%
                            </dd>
                        </div>
                    </dl>
                </section>

                <section class="mv-card p-6">
                    <h2 class="mv-card-title">Alertas</h2>

                    <div class="mt-5 space-y-3">
                        @forelse ($session->result?->impediments ?? [] as $impediment)
                            <div class="mv-card-muted p-4">
                                <p class="font-semibold text-ink-900">
                                    {{ $impediment->title }}
                                </p>

                                <p class="mv-section-description mt-1">
                                    {{ $impediment->message }}
                                </p>

                                @if ($impediment->recommendation)
                                    <p class="mt-2 text-xs font-semibold text-mvhab-primary">
                                        {{ $impediment->recommendation }}
                                    </p>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-ink-500">
                                Não foram encontrados impedimentos com os dados indicados.
                            </p>
                        @endforelse
                    </div>
                </section>
            </div>

            <aside class="space-y-6">
                <section class="mv-card p-6">
                    <h2 class="mv-card-title">Concursos recomendados</h2>

                    <div class="mt-5 space-y-3">
                        @forelse ($session->result?->recommendedContests ?? [] as $recommendation)
                            <a
                                href="{{ $recommendation->cta_url }}"
                                class="block rounded-2xl border border-ink-100 bg-white p-4 transition hover:border-mvhab-support hover:bg-mvhab-surface"
                            >
                                <p class="font-semibold text-ink-900">
                                    {{ $recommendation->contest->title }}
                                </p>

                                <p class="mv-section-description mt-1">
                                    {{ number_format((float) $recommendation->match_score, 0) }}% compatível
                                </p>
                            </a>
                        @empty
                            <p class="text-sm text-ink-500">
                                Sem concursos recomendados.
                            </p>
                        @endforelse
                    </div>

                    <div class="mt-6 grid gap-3">
                        <a href="{{ route('register') }}" class="mv-button-primary justify-center">
                            Criar conta
                        </a>

                        <a href="{{ route('public.simulator.show') }}" class="mv-button-secondary justify-center">
                            Nova simulação
                        </a>
                    </div>
                </section>
            </aside>
        </div>
    </section>
</x-public-layout>
