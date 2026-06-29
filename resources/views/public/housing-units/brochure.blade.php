<x-public-layout
    :title="'Brochura — '.$housingUnit->displayTitle()"
    :description="$housingUnit->public_summary ?: 'Brochura pública de habitação municipal.'"
    :canonical="route('public.housing-units.brochure', $housingUnit->public_slug)"
    :og-image="$ogImage ?? ($seo['og_image'] ?? null)"
    og-type="article"
>
    @php
        $contest = $contestHousingUnit?->contest;
        $program = $contest?->program;
        $municipality = $program?->municipality ?? $housingUnit->municipality;
    @endphp

    <section class="border-b border-ink-100 bg-white print:border-none">
        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center justify-between gap-4 print:hidden">
                <a href="{{ route('public.housing-units.show', $housingUnit->public_slug) }}" class="text-sm font-semibold text-mvhab-primary hover:text-mvhab-primary">Voltar à ficha</a>
                <button type="button" onclick="window.print()" class="mv-button-secondary">Imprimir / guardar PDF</button>
            </div>

            <div class="mt-8 rounded-md border border-ink-100 bg-white p-8 print:mt-0 print:border-none print:p-0">
                <p class="text-sm font-semibold uppercase tracking-wide text-mvhab-primary">{{ $municipality?->name ?? 'Município' }}</p>
                <h1 class="mt-3 text-3xl font-semibold text-ink-900">Brochura informativa</h1>
                <p class="mt-2 text-lg text-ink-600">{{ $housingUnit->displayTitle() }}</p>

                <dl class="mt-8 grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-500">Referência</dt>
                        <dd class="mt-1 text-base font-semibold text-ink-900">{{ $housingUnit->public_reference ?: $housingUnit->code }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-500">Concurso</dt>
                        <dd class="mt-1 text-base font-semibold text-ink-900">{{ $contest?->title ?? 'A confirmar' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-500">Programa</dt>
                        <dd class="mt-1 text-base font-semibold text-ink-900">{{ $program?->name ?? 'A confirmar' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-500">Estado</dt>
                        <dd class="mt-1 text-base font-semibold text-ink-900">{{ $housingUnit->public_status?->label() ?? 'A confirmar' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-500">Tipologia</dt>
                        <dd class="mt-1 text-base font-semibold text-ink-900">{{ $housingUnit->typology }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-500">Localização pública</dt>
                        <dd class="mt-1 text-base font-semibold text-ink-900">{{ $housingUnit->publicLocationLabel() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-500">Área útil</dt>
                        <dd class="mt-1 text-base font-semibold text-ink-900">{{ $housingUnit->usable_area_sqm ? number_format((float) $housingUnit->usable_area_sqm, 2, ',', ' ') . ' m²' : 'A confirmar' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-500">Área bruta</dt>
                        <dd class="mt-1 text-base font-semibold text-ink-900">{{ $housingUnit->gross_area_sqm ? number_format((float) $housingUnit->gross_area_sqm, 2, ',', ' ') . ' m²' : 'A confirmar' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-500">Renda mensal</dt>
                        <dd class="mt-1 text-base font-semibold text-ink-900">{{ $housingUnit->monthly_rent ? number_format((float) $housingUnit->monthly_rent, 2, ',', ' ') . ' €' : 'A confirmar' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-500">Eficiência energética</dt>
                        <dd class="mt-1 text-base font-semibold text-ink-900">{{ $housingUnit->energy_rating ?: 'A confirmar' }}</dd>
                    </div>
                </dl>

                <section class="mt-8">
                    <h2 class="text-xl font-semibold text-ink-900">Resumo</h2>
                    <p class="mt-3 whitespace-pre-line text-base leading-7 text-ink-600">{{ $housingUnit->public_summary ?: 'Habitação municipal disponível para consulta pública.' }}</p>
                </section>

                <section class="mt-8">
                    <h2 class="text-xl font-semibold text-ink-900">Como candidatar-se</h2>
                    <p class="mt-3 text-base leading-7 text-ink-600">A candidatura exige registo de adesão concluído, dados do agregado atualizados e documentação obrigatória submetida na área reservada. Confirme sempre os prazos e condições do aviso de abertura.</p>
                </section>

                <section class="mt-8 rounded-md bg-ink-50 p-5 print:border print:border-ink-100">
                    <h2 class="font-semibold text-ink-900">Nota de localização</h2>
                    <p class="mt-2 text-sm leading-6 text-ink-600">A localização apresentada é pública e pode ser aproximada. A morada completa só é apresentada quando autorizada pelo município e adequada ao procedimento.</p>
                </section>
            </div>
        </div>
    </section>
</x-public-layout>
