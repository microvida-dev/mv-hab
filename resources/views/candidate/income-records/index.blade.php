<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Etapa 3 de 4"
            title="Rendimentos"
            description="Declare os rendimentos de cada membro ou assinale a ausência de rendimentos."
        >
            <x-slot name="actions">
                <a href="{{ route('candidate.income-records.create') }}" class="mv-button-primary">
                    <x-ui-icon name="plus" class="h-4 w-4" />
                    Adicionar rendimento
                </a>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <x-candidate.registration-stepper :registration="$household->adhesionRegistration->loadMissing(['household.members.incomeRecords', 'currentHousingSituation'])" />

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <section class="grid gap-4 sm:grid-cols-3">
                <x-mv.stat-card
                    label="Mensal total"
                    :value="number_format($totals['monthly'], 2, ',', '.') . ' €'"
                />
                <x-mv.stat-card
                    label="Anual total"
                    :value="number_format($totals['annual'], 2, ',', '.') . ' €'"
                />
                <x-mv.stat-card
                    label="Média mensal por membro"
                    :value="number_format($household->members->count() ? $totals['monthly'] / $household->members->count() : 0, 2, ',', '.') . ' €'"
                />
            </section>

            <section class="space-y-4">
                @forelse ($household->members as $member)
                    <article class="mv-surface p-5">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <h2 class="font-semibold text-ink-900">{{ $member->full_name }}</h2>
                                <p class="mt-1 text-sm text-ink-500">{{ $member->relationship->label() }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-ink-500">Total mensal</p>
                                <p class="font-semibold text-ink-900">{{ number_format((float) $member->monthly_declared_income, 2, ',', '.') }} €</p>
                            </div>
                        </div>

                        @if ($member->has_no_income)
                            <x-mv.alert class="mt-4">
                                Sem rendimentos declarados{{ $member->no_income_reason ? ': '.$member->no_income_reason : '.' }}
                            </x-mv.alert>
                        @elseif ($member->incomeRecords->isEmpty())
                            <x-mv.alert class="mt-4">
                                Ainda não declarou rendimentos para este membro.
                            </x-mv.alert>
                        @else
                            <div class="mt-4 grid gap-3 md:grid-cols-2">
                                @foreach ($member->incomeRecords as $record)
                                    <div class="rounded-2xl border border-ink-100 p-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="font-semibold text-ink-900">{{ $record->incomeSource->name }}</p>
                                                <p class="mt-1 text-sm text-ink-500">{{ $record->description ?: 'Sem descrição adicional' }}</p>
                                            </div>
                                            <p class="whitespace-nowrap text-sm font-semibold text-ink-900">{{ number_format((float) $record->monthly_amount, 2, ',', '.') }} €</p>
                                        </div>
                                        <div class="mt-4 flex flex-wrap gap-2">
                                            <a href="{{ route('candidate.income-records.edit', $record) }}" class="mv-button-secondary">Editar</a>
                                            <form method="POST" action="{{ route('candidate.income-records.destroy', $record) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="mv-button-danger" onclick="return confirm('Remover este rendimento?')">Remover</button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </article>
                @empty
                    <x-mv.alert>
                        <strong>Ainda não existem membros no agregado.</strong>
                        <span class="mt-1 block">Adicione primeiro os membros do agregado para poder declarar rendimentos.</span>
                    </x-mv.alert>
                @endforelse
            </section>

            <x-mv.alert>
                Os valores apresentados resultam dos dados declarados e servem apenas para preparação do registo. A elegibilidade será avaliada posteriormente.
            </x-mv.alert>
        </div>
    </div>
</x-app-layout>
