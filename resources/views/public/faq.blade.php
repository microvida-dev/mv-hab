<x-public-layout
    title="Perguntas Frequentes"
    description="Respostas institucionais sobre programas, concursos e preparação de candidaturas ao Arrendamento Acessível."
>
    <section class="mv-section border-b border-ink-100 bg-white">
        <div class="mv-container">
            <p class="mv-caption">Apoio ao cidadão</p>

            <h1 class="mv-heading mt-3">
                Perguntas Frequentes
            </h1>

            <p class="mv-description mt-6 max-w-3xl">
                Informação geral e não vinculativa para compreender a plataforma e preparar as próximas etapas.
            </p>
        </div>
    </section>

    <section class="mv-section bg-mvhab-surface">
        <div class="mv-container max-w-4xl">
            <form method="GET" action="{{ route('public.faq') }}" class="mv-card mb-8 grid gap-4 p-6 sm:grid-cols-[1fr_auto]">
                <label for="faq-q">
                    <span class="mv-data-label">Pesquisar perguntas frequentes</span>
                    <input
                        id="faq-q"
                        name="q"
                        value="{{ $search ?? '' }}"
                        type="search"
                        class="mv-input mt-1"
                        placeholder="Candidatura, visitas, documentos..."
                    >
                </label>

                <div class="flex items-end">
                    <button type="submit" class="mv-button-primary w-full sm:w-auto">
                        Pesquisar
                    </button>
                </div>
            </form>

            <div class="mv-card divide-y divide-ink-100">
                @php
                    $fallbackFaqs = [
                        ['O que é o Arrendamento Acessível?', 'É uma resposta municipal destinada a promover o acesso a habitação para arrendamento em condições definidas pelos regulamentos e avisos aplicáveis.'],
                        ['Quem pode candidatar-se?', 'Os requisitos concretos dependem do regulamento municipal e do aviso de cada concurso. Consulte sempre a página do concurso e os documentos oficiais associados.'],
                        ['Como sei se existe concurso aberto?', 'A página de concursos identifica os avisos publicados e apresenta o respetivo estado e período de candidatura.'],
                        ['Como posso consultar os programas disponíveis?', 'A página de programas reúne a informação pública, regras gerais e concursos associados a cada programa municipal.'],
                        ['O que devo preparar antes de uma candidatura?', 'Prepare os seus dados de identificação, composição do agregado, rendimentos e situação habitacional. Os documentos obrigatórios serão definidos em cada aviso.'],
                        ['Como serei notificado durante o processo?', 'Os canais e momentos de comunicação serão definidos nas próximas etapas e de acordo com as regras de cada procedimento.'],
                        ['A candidatura online já está disponível?', 'Ainda não. Nesta fase pode consultar programas, concursos e prazos. A submissão formal será disponibilizada numa etapa posterior.'],
                        ['Onde posso pedir apoio?', 'Utilize os canais oficiais do município indicados no aviso do concurso. Não envie documentos pessoais por canais não confirmados.'],
                    ];
                @endphp

                @forelse ($faqs as $faq)
                    <details class="group p-5">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-ink-900">
                            {{ $faq->question }}
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-mvhab-surface text-mvhab-primary" aria-hidden="true">
                                +
                            </span>
                        </summary>

                        <p class="mv-section-description mt-4 max-w-3xl">
                            {{ $faq->answer }}
                        </p>
                    </details>
                @empty
                    @if (($search ?? '') !== '' || ($category ?? '') !== '')
                        <div class="p-8 text-center">
                            <p class="font-semibold text-ink-900">
                                Não foram encontradas perguntas frequentes
                            </p>

                            <p class="mv-section-description mt-2">
                                Ajuste a pesquisa ou consulte novamente mais tarde.
                            </p>
                        </div>
                    @else
                        @foreach ($fallbackFaqs as [$question, $answer])
                            <details class="group p-5">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-ink-900">
                                    {{ $question }}
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-mvhab-surface text-mvhab-primary" aria-hidden="true">
                                        +
                                    </span>
                                </summary>

                                <p class="mv-section-description mt-4 max-w-3xl">
                                    {{ $answer }}
                                </p>
                            </details>
                        @endforeach
                    @endif
                @endforelse
            </div>

            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('public.contests.index') }}" class="mv-button-primary">
                    Consultar concursos
                </a>

                <a href="{{ route('public.programs.index') }}" class="mv-button-secondary">
                    Consultar programas
                </a>

                <a href="{{ route('public.simulator.show') }}" class="mv-button-secondary">
                    Simular elegibilidade
                </a>
            </div>
        </div>
    </section>
</x-public-layout>
