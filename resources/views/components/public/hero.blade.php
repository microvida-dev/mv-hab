<section class="relative overflow-hidden bg-civic-950 text-white">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(255,255,255,0.16),transparent_34rem)]"></div>
    <div class="absolute inset-y-0 right-0 hidden w-1/2 bg-civic-900/40 lg:block"></div>

    <div class="relative mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:px-6 md:py-24 lg:grid-cols-[minmax(0,1fr)_24rem] lg:px-8">
        <div class="max-w-3xl">
            <p class="text-sm font-semibold uppercase tracking-wide text-civic-100">
                Portal municipal de habitação
            </p>

            <h1 class="mt-4 text-4xl font-semibold leading-tight sm:text-5xl lg:text-6xl">
                Habitação Municipal Acessível
            </h1>

            <p class="mt-6 max-w-2xl text-lg leading-8 text-civic-100">
                Consulte concursos, descubra habitações disponíveis, simule a sua elegibilidade e acompanhe o processo online.
            </p>

            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('public.contests.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-md bg-white px-5 py-3 text-sm font-semibold text-civic-950 shadow-sm hover:bg-civic-50">
                    Ver concursos
                </a>

                <a href="{{ route('public.simulator.show') }}" class="inline-flex min-h-12 items-center justify-center rounded-md bg-signal-400 px-5 py-3 text-sm font-semibold text-ink-950 shadow-sm hover:bg-signal-300">
                    Simular elegibilidade
                </a>

                <a href="{{ route('public.housing-offer.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-md border border-white/30 px-5 py-3 text-sm font-semibold text-white hover:bg-white/10">
                    Oferta habitacional
                </a>
            </div>
        </div>

        <aside class="rounded-2xl border border-white/15 bg-white/10 p-6 shadow-2xl backdrop-blur">
            <p class="text-sm font-semibold text-civic-100">
                Estado atual
            </p>

            <dl class="mt-5 grid gap-4">
                <div class="rounded-xl bg-white p-4 text-ink-900">
                    <dt class="text-sm font-medium text-ink-500">Concursos publicados</dt>
                    <dd class="mt-1 text-3xl font-semibold">{{ $contests->count() }}</dd>
                </div>

                <div class="rounded-xl bg-white p-4 text-ink-900">
                    <dt class="text-sm font-medium text-ink-500">Programas disponíveis</dt>
                    <dd class="mt-1 text-3xl font-semibold">{{ $programs->count() }}</dd>
                </div>

                <div class="rounded-xl bg-white p-4 text-ink-900">
                    <dt class="text-sm font-medium text-ink-500">Próximo passo recomendado</dt>
                    <dd class="mt-2 text-sm font-semibold text-civic-800">
                        Simular elegibilidade antes de iniciar candidatura
                    </dd>
                </div>
            </dl>
        </aside>
    </div>
</section>
