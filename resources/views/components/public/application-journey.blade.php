<section class="bg-mvhab-card py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        <div class="mx-auto max-w-3xl text-center">
            <p class="text-sm font-semibold uppercase tracking-wide text-mvhab-primary">
                Como funciona
            </p>

            <h2 class="mt-3 text-4xl font-bold text-ink-900">
                O seu percurso em apenas alguns passos
            </h2>

            <p class="mt-5 text-lg text-ink-600">
                Desde a consulta dos concursos até à assinatura do contrato, todo o processo pode ser acompanhado através da plataforma.
            </p>
        </div>

        @php
            $steps = [
                ['01', 'Criar conta', 'Registe-se gratuitamente na plataforma.'],
                ['02', 'Simular elegibilidade', 'Verifique se reúne as condições para concorrer.'],
                ['03', 'Escolher concurso', 'Consulte os concursos disponíveis e selecione o mais adequado.'],
                ['04', 'Submeter candidatura', 'Preencha o formulário e envie a documentação.'],
                ['05', 'Acompanhar processo', 'Receba notificações e acompanhe todas as fases da candidatura.'],
                ['06', 'Celebrar contrato', 'Em caso de aprovação, conclua o processo de atribuição da habitação.'],
            ];
        @endphp

        <div class="mt-16 grid gap-6 md:grid-cols-2 xl:grid-cols-3">

            @foreach($steps as [$number, $title, $description])

                <div class="group rounded-2xl border border-ink-100 bg-mvhab-card p-8 shadow-surface transition-all duration-300 hover:-translate-y-1 hover:border-mvhab-support hover:shadow-lg">

                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-mvhab-primary text-lg font-bold text-white">
                        {{ $number }}
                    </div>

                    <h3 class="mt-6 text-xl font-semibold text-ink-900">
                        {{ $title }}
                    </h3>

                    <p class="mt-3 leading-7 text-ink-600">
                        {{ $description }}
                    </p>

                </div>

            @endforeach

        </div>

    </div>
</section>
