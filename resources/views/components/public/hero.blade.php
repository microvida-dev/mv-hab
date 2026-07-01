@props(['stats'])

<section class="relative isolate overflow-hidden text-white">
    <img
        src="{{ asset('images/hero/mvhab-hero-comp.png') }}"
        alt=""
        aria-hidden="true"
        class="absolute inset-0 h-full w-full object-cover"
    >

    <div class="absolute inset-0 bg-gradient-to-r from-mvhab-primary/95 via-mvhab-primary/80 to-mvhab-primary/20"></div>
    <div class="absolute inset-0 bg-black/10"></div>

    <div class="mv-container relative grid gap-12 py-24 lg:grid-cols-[minmax(0,1fr)_25rem] lg:py-32">
        <div class="max-w-3xl">
            <p class="mv-caption text-white/85">Portal Municipal de Habitação</p>

            <h1 class="mt-5 text-5xl font-bold leading-tight tracking-tight text-white lg:text-7xl">
                Habitação Municipal
                <br>
                Acessível
            </h1>

            <p class="mt-8 max-w-2xl text-xl leading-9 text-white/90">
                Consulte concursos, descubra habitações disponíveis, simule a sua elegibilidade e acompanhe todo o processo online.
            </p>

            <div class="mt-10 flex flex-wrap gap-4">
                <a href="{{ route('public.contests.index') }}" class="mv-button-secondary min-h-14 px-7 text-base">
                    Ver concursos
                </a>

                <a href="{{ route('public.simulator.show') }}" class="mv-button-primary min-h-14 px-7 text-base">
                    Simular elegibilidade
                </a>

                <a href="{{ route('public.housing-offer.index') }}" class="inline-flex min-h-14 items-center justify-center gap-2 rounded-2xl border border-white/40 bg-white/5 px-7 text-base font-semibold text-white backdrop-blur transition duration-200 hover:bg-white/15">
                    Oferta habitacional
                </a>
            </div>
        </div>

        <aside class="mv-card rounded-3xl bg-white/90 p-7 backdrop-blur-xl">
            <h2 class="mv-card-title text-mvhab-primary">Estado atual</h2>

            <div class="mt-6 space-y-4">
                <div class="mv-card flex items-center gap-4 p-5">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-mvhab-surface text-mvhab-primary">
                        <x-mv-icon name="contest" size="lg" />
                    </div>
                    <div>
                        <p class="mv-data-label">Concursos publicados</p>
                        <p class="mt-1 text-4xl font-bold text-ink-900">{{ $stats['publishedContests'] }}</p>
                    </div>
                </div>

                <div class="mv-card flex items-center gap-4 p-5">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-mvhab-surface text-mvhab-primary">
                        <x-mv-icon name="program" size="lg" />
                    </div>
                    <div>
                        <p class="mv-data-label">Programas disponíveis</p>
                        <p class="mt-1 text-4xl font-bold text-ink-900">{{ $stats['publishedPrograms'] }}</p>
                    </div>
                </div>

                <div class="mv-card flex items-center gap-4 p-5">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-mvhab-surface text-mvhab-primary">
                        <x-mv-icon name="housing" size="lg" />
                    </div>
                    <div>
                        <p class="mv-data-label">Habitações disponíveis</p>
                        <p class="mt-1 text-4xl font-bold text-ink-900">{{ $stats['availableHousingUnits'] }}</p>
                    </div>
                </div>

                <div class="mv-card flex items-center gap-4 p-5">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-mvhab-surface text-mvhab-primary">
                        <x-mv-icon name="simulator" size="lg" />
                    </div>
                    <div class="flex-1">
                        <p class="mv-data-label">Próximo passo recomendado</p>
                        <p class="mt-1 text-base font-semibold text-ink-900">
                            Simular elegibilidade antes de iniciar candidatura
                        </p>
                    </div>
                    <x-mv-icon name="arrow-right" size="sm" class="text-ink-400" />
                </div>
            </div>
        </aside>
    </div>
</section>
