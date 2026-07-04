<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Etapa 2 de 4"
            title="Agregado familiar"
            description="Indique as pessoas que integram o seu agregado habitacional."
        >
            @if ($household)
                <x-slot name="actions">
                    <a href="{{ route('candidate.household.edit') }}" class="mv-button-secondary">Editar dados gerais</a>
                </x-slot>
            @endif
        </x-mv.page-header>
    </x-slot>

    <x-candidate.registration-stepper :registration="$registration->loadMissing(['household.members.incomeRecords', 'currentHousingSituation'])" />

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            @if (! $household)
                <x-mv.section
                    title="Crie o seu agregado"
                    description="Ao criar o agregado, o requerente principal será sincronizado com os dados do Registo de Adesão. Poderá depois adicionar os restantes membros."
                >
                    <div class="max-w-2xl">
                        <form method="POST" action="{{ route('candidate.household.store') }}" class="mt-6">
                            @csrf
                            <input type="hidden" name="household_type" value="family">
                            <button type="submit" class="mv-button-primary">
                                <x-ui-icon name="plus" class="h-4 w-4" />
                                Criar agregado
                            </button>
                        </form>
                    </div>
                </x-mv.section>
            @else
                <section class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
                    <x-mv.section>
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
                                <article class="rounded-2xl border border-ink-100 p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-semibold text-ink-900">{{ $member->full_name }}</p>
                                            <p class="mt-1 text-sm text-ink-500">{{ $member->relationship->label() }} · {{ $member->age() ?? 'Idade por indicar' }}</p>
                                        </div>
                                        @if ($member->is_applicant)
                                            <x-mv.badge tone="success">Requerente</x-mv.badge>
                                        @endif
                                    </div>
                                    <p class="mt-3 text-sm text-ink-600">
                                        {{ $member->has_no_income ? 'Sem rendimentos declarados' : number_format((float) $member->monthly_declared_income, 2, ',', '.').' € / mês' }}
                                    </p>
                                </article>
                            @endforeach
                        </div>

                        <a href="{{ route('candidate.household-members.index') }}" class="mv-button-secondary mt-6">Gerir membros</a>
                    </x-mv.section>

                    <aside class="space-y-4">
                        <x-mv.stat-card
                            label="Dependentes"
                            :value="$household->members->where('is_dependent', true)->count()"
                        />
                        <x-mv.stat-card
                            label="Estudantes"
                            :value="$household->members->where('is_student', true)->count()"
                        />
                        <x-mv.stat-card
                            label="Rendimento mensal"
                            :value="number_format((float) $household->monthly_income, 2, ',', '.') . ' €'"
                        />
                    </aside>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
