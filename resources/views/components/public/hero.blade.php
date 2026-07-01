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
            <p class="mv-caption text-white/85">
                Portal Municipal de Habitação
            </p>

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
            <h2 class="mv-card-title text-mvhab-primary">
                Estado atual
            </h2>

            <div class="mt-6 space-y-4">
                <div class="mv-card flex items-center gap-4 p-5">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-mvhab-surface text-mvhab-primary">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5 12 3l9 7.5M5.25 9.75V21h13.5V9.75M9 21v-6h6v6" />
                        </svg>
                    </div>
                    <div>
                        <p class="mv-data-label">Concursos publicados</p>
                        <p class="mt-1 text-4xl font-bold text-ink-900">{{ $stats['publishedContests'] }}</p>
                    </div>
                </div>

                <div class="mv-card flex items-center gap-4 p-5">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-mvhab-surface text-mvhab-primary">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M6 21V4.5A1.5 1.5 0 0 1 7.5 3h9A1.5 1.5 0 0 1 18 4.5V21M9 7.5h.01M12 7.5h.01M15 7.5h.01M9 11h.01M12 11h.01M15 11h.01M9 14.5h.01M12 14.5h.01M15 14.5h.01" />
                        </svg>
                    </div>
                    <div>
                        <p class="mv-data-label">Programas disponíveis</p>
                        <p class="mt-1 text-4xl font-bold text-ink-900">{{ $stats['publishedPrograms'] }}</p>
                    </div>
                </div>

                <div class="mv-card flex items-center gap-4 p-5">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-mvhab-surface text-mvhab-primary">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 7.5a4.5 4.5 0 1 1-3.18 7.68L9 18.75H6.75V21H3v-3.75l5.82-5.82A4.5 4.5 0 0 1 15.75 7.5Z" />
                        </svg>
                    </div>
                    <div>
                        <p class="mv-data-label">Habitações disponíveis</p>
                        <p class="mt-1 text-4xl font-bold text-ink-900">{{ $stats['availableHousingUnits'] }}</p>
                    </div>
                </div>

                <div class="mv-card flex items-center gap-4 p-5">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-mvhab-surface text-mvhab-primary">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3.75 8.25h16.5M5.25 5.25h13.5A1.5 1.5 0 0 1 20.25 6.75v12A1.5 1.5 0 0 1 18.75 20.25H5.25A1.5 1.5 0 0 1 3.75 18.75v-12A1.5 1.5 0 0 1 5.25 5.25Z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="mv-data-label">Próximo passo recomendado</p>
                        <p class="mt-1 text-base font-semibold text-ink-900">
                            Simular elegibilidade antes de iniciar candidatura
                        </p>
                    </div>
                    <svg class="h-5 w-5 text-ink-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" />
                    </svg>
                </div>
            </div>
        </aside>
    </div>
</section>
