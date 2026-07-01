@props(['contests'])

<section class="mv-section bg-mvhab-surface">
    <div class="mv-container">

        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">

            <div class="max-w-3xl">

                <p class="mv-caption">
                    Concursos
                </p>

                <h2 class="mv-heading mt-3">
                    Oportunidades publicadas
                </h2>

                <p class="mv-description mt-6">
                    Consulte os concursos municipais atualmente publicados, conheça os respetivos prazos, programas associados e acompanhe todas as oportunidades de acesso à habitação municipal.
                </p>

            </div>

            <div class="flex items-center">

                <a
                    href="{{ route('public.contests.index') }}"
                    class="mv-button-secondary"
                >
                    Ver todos os concursos

                    <x-mv-icon
                        name="external"
                        size="sm"
                        class="ml-2"
                    />

                </a>

            </div>

        </div>

        <div class="mt-12 grid gap-8 md:grid-cols-2 xl:grid-cols-3">

            @forelse ($contests as $contest)

                <x-public-contest-card
                    :contest="$contest"
                />

            @empty

                <div class="mv-card col-span-full py-16 text-center">

                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-mvhab-surface">

                        <x-mv-icon
                            name="contest"
                            size="xl"
                            class="text-mvhab-primary"
                        />

                    </div>

                    <h3 class="mt-8 text-xl font-semibold text-ink-900">
                        Não existem concursos publicados
                    </h3>

                    <p class="mv-description mx-auto mt-4 max-w-xl">
                        Neste momento não existem concursos municipais disponíveis. Assim que forem publicados novos procedimentos, passarão a estar visíveis nesta página.
                    </p>

                </div>

            @endforelse

        </div>

    </div>
</section>
