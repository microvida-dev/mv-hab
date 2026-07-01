<section class="mv-section bg-white">
    <div class="mv-container">

        <div class="mx-auto max-w-3xl text-center">
            <p class="mv-caption">
                Como funciona
            </p>

            <h2 class="mv-heading mt-3">
                O seu percurso em apenas alguns passos
            </h2>

            <p class="mv-description mt-6">
                Desde a consulta dos concursos até à assinatura do contrato, todo o processo pode ser acompanhado através da plataforma.
            </p>
        </div>

        @php
            $steps = [
                [
                    'number' => '01',
                    'icon' => 'profile',
                    'title' => 'Criar conta',
                    'description' => 'Registe-se gratuitamente na plataforma.',
                ],
                [
                    'number' => '02',
                    'icon' => 'simulator',
                    'title' => 'Simular elegibilidade',
                    'description' => 'Verifique se reúne as condições para concorrer.',
                ],
                [
                    'number' => '03',
                    'icon' => 'contest',
                    'title' => 'Escolher concurso',
                    'description' => 'Consulte os concursos disponíveis e selecione o mais adequado.',
                ],
                [
                    'number' => '04',
                    'icon' => 'application',
                    'title' => 'Submeter candidatura',
                    'description' => 'Preencha o formulário e envie toda a documentação necessária.',
                ],
                [
                    'number' => '05',
                    'icon' => 'notification',
                    'title' => 'Acompanhar processo',
                    'description' => 'Receba notificações e acompanhe todas as fases da candidatura.',
                ],
                [
                    'number' => '06',
                    'icon' => 'contract',
                    'title' => 'Celebrar contrato',
                    'description' => 'Após aprovação, conclua a atribuição da habitação e celebre o contrato.',
                ],
            ];
        @endphp

        <div class="mt-16 grid gap-8 md:grid-cols-2 xl:grid-cols-3">

            @foreach ($steps as $step)

                <article class="mv-card-interactive relative overflow-hidden p-8">

                    <div class="absolute right-6 top-6 text-5xl font-bold text-mvhab-primary/10">
                        {{ $step['number'] }}
                    </div>

                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-mvhab-surface">

                        <x-mv-icon
                            :name="$step['icon']"
                            class="h-8 w-8 text-mvhab-primary"
                        />

                    </div>

                    <h3 class="mv-card-title mt-8">
                        {{ $step['title'] }}
                    </h3>

                    <p class="mv-description mt-3">
                        {{ $step['description'] }}
                    </p>

                    @if (! $loop->last)
                        <div class="mt-8 flex items-center text-sm font-semibold text-mvhab-primary">
                            Próximo passo

                            <svg
                                class="ml-2 h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 5l7 7-7 7"
                                />
                            </svg>
                        </div>
                    @endif

                </article>

            @endforeach

        </div>

    </div>
</section>
