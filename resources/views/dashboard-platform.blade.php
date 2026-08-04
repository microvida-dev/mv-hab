<x-app-layout>
    <x-slot name="header"></x-slot>

    <div class="py-8">
        <div class="mv-page-shell">
            <x-flash-message />

            <section class="mv-card overflow-hidden">
                <div class="border-b border-ink-100 bg-gradient-to-br from-mvhab-surface via-white to-white px-6 py-8 sm:px-8">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-mvhab-primary">
                        {{ data_get($dashboard, 'adaptive_dashboard.eyebrow', 'Administração da plataforma') }}
                    </p>

                    <h1 class="mt-3 text-3xl font-semibold tracking-tight text-ink-950">
                        {{ data_get($dashboard, 'adaptive_dashboard.headline', 'Visão global da plataforma') }}
                    </h1>

                    <p class="mt-3 max-w-3xl text-sm leading-6 text-ink-600">
                        {{ data_get(
                            $dashboard,
                            'adaptive_dashboard.description',
                            'Acompanhe Municípios, segurança, acessos, onboarding e operação transversal do MV-HAB.',
                        ) }}
                    </p>
                </div>

                <div class="grid gap-5 p-6 sm:p-8 lg:grid-cols-2">
                    <section class="rounded-3xl border border-ink-100 bg-white p-5 shadow-surface">
                        <div class="flex items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-mvhab-surface text-mvhab-primary">
                                <x-mv-icon name="security" size="sm" />
                            </span>

                            <div>
                                <h2 class="text-base font-semibold text-ink-950">
                                    Operação global protegida
                                </h2>

                                <p class="mt-2 text-sm leading-6 text-ink-600">
                                    Sem contexto municipal ativo, não são carregados indicadores,
                                    processos, documentos, prazos ou métricas de qualquer Município.
                                </p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-3xl border border-ink-100 bg-white p-5 shadow-surface">
                        <div class="flex items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-mvhab-surface text-mvhab-primary">
                                <x-mv-icon name="housing" size="sm" />
                            </span>

                            <div>
                                <h2 class="text-base font-semibold text-ink-950">
                                    Contexto municipal obrigatório
                                </h2>

                                <p class="mt-2 text-sm leading-6 text-ink-600">
                                    A operação local exige a seleção explícita e auditada de um
                                    Município autorizado. A identidade global permanece inalterada.
                                </p>
                            </div>
                        </div>
                    </section>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
