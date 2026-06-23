<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Etapa 2 de 4</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">Agregado familiar</h1>
                <p class="mt-1 text-sm text-ink-500">Indique as pessoas que integram o seu agregado habitacional.</p>
            </div>
            @if ($household)
                <a href="{{ route('candidate.household.edit') }}" class="mv-button-secondary">Editar dados gerais</a>
            @endif
        </div>
    </x-slot>

    <x-candidate.registration-stepper :registration="$registration->loadMissing(['household.members.incomeRecords', 'currentHousingSituation'])" />

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            @if (! $household)
                <section class="mv-surface p-6">
                    <div class="max-w-2xl">
                        <h2 class="text-xl font-semibold text-ink-900">Crie o seu agregado</h2>
                        <p class="mt-2 text-sm leading-6 text-ink-600">Ao criar o agregado, o requerente principal será sincronizado com os dados do Registo de Adesão. Poderá depois adicionar os restantes membros.</p>
                        <form method="POST" action="{{ route('candidate.household.store') }}" class="mt-6">
                            @csrf
                            <input type="hidden" name="household_type" value="family">
                            <button type="submit" class="mv-button-primary">
                                <x-ui-icon name="plus" class="h-4 w-4" />
                                Criar agregado
                            </button>
                        </form>
                    </div>
                </section>
            @else
                <section class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
                    <div class="mv-surface p-6">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <h2 class="text-xl font-semibold text-ink-900">{{ $household->name }}</h2>
                                <p class="mt-1 text-sm text-ink-500">{{ $household->members->count() }} membro(s) registado(s)</p>
                            </div>
                            <a href="{{ route('candidate.household-members.create') }}" class="mv-button-primary">
                                <x-ui-icon name="plus" class="h-4 w-4" />
                                Adicionar membro
                            </a>
                        </div>

                        <div class="mt-6 grid gap-3 sm:grid-cols-2">
                            @foreach ($household->members as $member)
                                <article class="rounded-md border border-ink-100 p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-semibold text-ink-900">{{ $member->full_name }}</p>
                                            <p class="mt-1 text-sm text-ink-500">{{ $member->relationship->label() }} · {{ $member->age() ?? 'Idade por indicar' }}</p>
                                        </div>
                                        @if ($member->is_applicant)
                                            <span class="rounded-md bg-civic-50 px-2 py-1 text-xs font-semibold text-civic-700">Requerente</span>
                                        @endif
                                    </div>
                                    <p class="mt-3 text-sm text-ink-600">
                                        {{ $member->has_no_income ? 'Sem rendimentos declarados' : number_format((float) $member->monthly_declared_income, 2, ',', '.').' € / mês' }}
                                    </p>
                                </article>
                            @endforeach
                        </div>

                        <a href="{{ route('candidate.household-members.index') }}" class="mv-button-secondary mt-6">Gerir membros</a>
                    </div>

                    <aside class="mv-surface p-5">
                        <h2 class="font-semibold text-ink-900">Resumo</h2>
                        <dl class="mt-4 space-y-4 text-sm">
                            <div>
                                <dt class="text-ink-500">Dependentes</dt>
                                <dd class="mt-1 font-semibold text-ink-900">{{ $household->members->where('is_dependent', true)->count() }}</dd>
                            </div>
                            <div>
                                <dt class="text-ink-500">Estudantes</dt>
                                <dd class="mt-1 font-semibold text-ink-900">{{ $household->members->where('is_student', true)->count() }}</dd>
                            </div>
                            <div>
                                <dt class="text-ink-500">Rendimento mensal</dt>
                                <dd class="mt-1 font-semibold text-ink-900">{{ number_format((float) $household->monthly_income, 2, ',', '.') }} €</dd>
                            </div>
                        </dl>
                    </aside>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
