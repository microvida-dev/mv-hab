@props(['stats'])

<section class="relative overflow-hidden bg-gradient-to-br from-mvhab-primary via-mvhab-primaryLight to-mvhab-secondary text-white">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(255,255,255,0.16),transparent_34rem)]"></div>
    <div class="absolute inset-y-0 right-0 hidden w-1/2 bg-gradient-to-l from-black/10 to-transparent lg:block"></div>

    <div class="relative mx-auto grid max-w-7xl gap-10 px-4 py-24 sm:px-6 md:py-32 lg:grid-cols-[minmax(0,1fr)_24rem] lg:px-8">
        <div class="max-w-3xl">
            <p class="text-sm font-semibold uppercase tracking-wide text-white/80">
                Portal municipal de habitação
            </p>

            <h1 class="mt-4 text-4xl font-semibold leading-tight sm:text-5xl lg:text-6xl">
                Habitação Municipal Acessível
            </h1>

            <p class="mt-6 max-w-2xl text-lg leading-8 text-white/90">
                Consulte concursos, descubra habitações disponíveis, simule a sua elegibilidade e acompanhe o processo online.
            </p>

            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('public.contests.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-mvhab-primary shadow-sm transition hover:bg-mvhab-surface hover:text-mvhab-primary">
                    Ver concursos
                </a>

                <a href="{{ route('public.simulator.show') }}" class="inline-flex min-h-12 items-center justify-center rounded-2xl bg-mvhab-secondary px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-white hover:text-mvhab-primary">
                    Simular elegibilidade
                </a>

                <a href="{{ route('public.housing-offer.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-2xl border border-white/40 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-white/10">
                    Oferta habitacional
                </a>
            </div>
        </div>

        <aside class="rounded-2xl border border-white/15 bg-white/20 p-6 shadow-2xl backdrop-blur-xl">
            <p class="text-sm font-semibold text-white/90">
                Estado atual
            </p>

            <dl class="mt-5 grid gap-4">
                <div class="rounded-xl bg-white p-4 text-ink-900">
                    <dt class="text-sm font-medium text-ink-500">Concursos publicados</dt>
                    <dd class="mt-1 text-3xl font-semibold">{{ $stats['publishedContests'] }}</dd>
                </div>

                <div class="rounded-xl bg-white p-4 text-ink-900">
                    <dt class="text-sm font-medium text-ink-500">Programas disponíveis</dt>
                    <dd class="mt-1 text-3xl font-semibold">{{ $stats['publishedPrograms'] }}</dd>
                </div>

                <div class="rounded-xl bg-white p-4 text-ink-900">
                    <dt class="text-sm font-medium text-ink-500">Habitações disponíveis</dt>
                    <dd class="mt-1 text-3xl font-semibold">{{ $stats['availableHousingUnits'] }}</dd>
                </div>

                <div class="rounded-xl bg-white p-4 text-ink-900">
                    <dt class="text-sm font-medium text-ink-500">Próximo passo recomendado</dt>
                    <dd class="mt-2 text-sm font-semibold text-mvhab-primary">
                        Simular elegibilidade antes de iniciar candidatura
                    </dd>
                </div>
            </dl>
        </aside>
    </div>
</section>
