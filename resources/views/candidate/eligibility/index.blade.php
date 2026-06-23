<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Condições mínimas de acesso</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">Elegibilidade</h1>
                <p class="mt-1 text-sm text-ink-500">Faça uma verificação indicativa com os dados atualmente declarados.</p>
            </div>
            <a href="{{ route('candidate.eligibility.history') }}" class="mv-button-secondary">Ver histórico</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <section class="border-l-4 border-civic-700 bg-civic-50 p-5 text-sm leading-6 text-civic-900">
                Esta verificação é indicativa e baseia-se nos dados atualmente declarados. A decisão final depende da análise dos serviços municipais e das regras do programa ou concurso.
            </section>

            @if ($latestCheck)
                <section class="mv-surface p-6">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-civic-700">Última verificação</p>
                            <h2 class="mt-1 text-xl font-semibold text-ink-900">{{ $latestCheck->result->label() }}</h2>
                            <p class="mt-2 max-w-3xl text-sm leading-6 text-ink-600">{{ $latestCheck->summary }}</p>
                        </div>
                        <a href="{{ route('candidate.eligibility.show', $latestCheck) }}" class="mv-button-secondary">Consultar resultado</a>
                    </div>
                </section>
            @endif

            <section>
                <h2 class="text-lg font-semibold text-ink-900">Programas e concursos disponíveis</h2>
                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                    @forelse ($ruleSets as $ruleSet)
                        <article class="mv-surface p-6">
                            <p class="text-xs font-semibold uppercase text-ink-500">
                                {{ $ruleSet->contest ? 'Concurso' : 'Programa' }}
                            </p>
                            <h3 class="mt-2 text-lg font-semibold text-ink-900">
                                {{ $ruleSet->contest?->title ?? $ruleSet->program?->name }}
                            </h3>
                            <p class="mt-2 text-sm leading-6 text-ink-600">{{ $ruleSet->description ?: 'Conjunto de condições mínimas atualmente em vigor.' }}</p>
                            <form method="POST" action="{{ route('candidate.eligibility.pre-check') }}" class="mt-5">
                                @csrf
                                @if ($ruleSet->contest_id)
                                    <input type="hidden" name="contest_id" value="{{ $ruleSet->contest_id }}">
                                @else
                                    <input type="hidden" name="program_id" value="{{ $ruleSet->program_id }}">
                                @endif
                                <button type="submit" class="mv-button-primary">
                                    <x-ui-icon name="check" class="h-4 w-4" />
                                    Executar pré-verificação
                                </button>
                            </form>
                        </article>
                    @empty
                        <div class="mv-surface p-6 lg:col-span-2">
                            <p class="text-sm text-ink-600">Ainda não existem regras de elegibilidade ativas para pré-verificação.</p>
                            <a href="{{ route('public.contests.index') }}" class="mt-4 inline-flex text-sm font-semibold text-civic-700">Ver concursos</a>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
