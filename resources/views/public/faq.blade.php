<x-public-layout
    title="Perguntas Frequentes"
    description="Respostas institucionais sobre programas, concursos e preparação de candidaturas ao Arrendamento Acessível."
>
    <section class="border-b border-ink-100 bg-ink-50">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <p class="text-sm font-semibold text-mvhab-primary">Apoio ao cidadão</p>
            <h1 class="mt-2 text-3xl font-semibold text-ink-900">Perguntas Frequentes</h1>
            <p class="mt-3 max-w-3xl text-base leading-7 text-ink-500">Informação geral e não vinculativa para compreender a plataforma e preparar as próximas etapas.</p>
        </div>
    </section>

    <section class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        <form method="GET" action="{{ route('public.faq') }}" class="mb-8 grid gap-4 border-b border-ink-100 pb-6 sm:grid-cols-[1fr_auto]">
            <div>
                <label for="faq-q" class="block text-sm font-semibold text-ink-800">Pesquisar perguntas frequentes</label>
                <input
                    id="faq-q"
                    name="q"
                    value="{{ $search ?? '' }}"
                    type="search"
                    class="mt-2 w-full rounded-md border border-ink-200 px-3 py-2 text-sm text-ink-900 focus:border-mvhab-primary focus:outline-none focus:ring-2 focus:ring-mvhab-primary/20"
                    placeholder="Candidatura, visitas, documentos..."
                >
            </div>
            <div class="flex items-end">
                <button type="submit" class="mv-button-primary w-full sm:w-auto">Pesquisar</button>
            </div>
        </form>

        <div class="divide-y divide-ink-100 border-y border-ink-100">
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
                <details class="group py-5">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-ink-900">
                        {{ $faq->question }}
                        <span class="text-xl font-normal text-mvhab-primary" aria-hidden="true">+</span>
                    </summary>
                    <p class="mt-3 max-w-3xl text-sm leading-6 text-ink-600">{{ $faq->answer }}</p>
                </details>
            @empty
                @if (($search ?? '') !== '' || ($category ?? '') !== '')
                    <p class="py-8 text-sm text-ink-600">Não foram encontradas perguntas frequentes para a pesquisa indicada.</p>
                @else
                    @foreach ($fallbackFaqs as [$question, $answer])
                    <details class="group py-5">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-ink-900">
                            {{ $question }}
                            <span class="text-xl font-normal text-mvhab-primary" aria-hidden="true">+</span>
                        </summary>
                        <p class="mt-3 max-w-3xl text-sm leading-6 text-ink-600">{{ $answer }}</p>
                    </details>
                    @endforeach
                @endif
            @endforelse
        </div>

        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('public.contests.index') }}" class="mv-button-primary">Consultar concursos</a>
            <a href="{{ route('public.programs.index') }}" class="mv-button-secondary">Consultar programas</a>
        </div>
    </section>
</x-public-layout>
