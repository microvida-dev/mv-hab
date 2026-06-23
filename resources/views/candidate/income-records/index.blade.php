<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Etapa 3 de 4</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">Rendimentos</h1>
                <p class="mt-1 text-sm text-ink-500">Declare os rendimentos de cada membro ou assinale a ausência de rendimentos.</p>
            </div>
            <a href="{{ route('candidate.income-records.create') }}" class="mv-button-primary">
                <x-ui-icon name="plus" class="h-4 w-4" />
                Adicionar rendimento
            </a>
        </div>
    </x-slot>

    <x-candidate.registration-stepper :registration="$household->adhesionRegistration->loadMissing(['household.members.incomeRecords', 'currentHousingSituation'])" />

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <section class="grid gap-4 sm:grid-cols-3">
                <div class="mv-surface p-5">
                    <p class="text-sm text-ink-500">Mensal total</p>
                    <p class="mt-2 text-2xl font-semibold text-ink-900">{{ number_format($totals['monthly'], 2, ',', '.') }} €</p>
                </div>
                <div class="mv-surface p-5">
                    <p class="text-sm text-ink-500">Anual total</p>
                    <p class="mt-2 text-2xl font-semibold text-ink-900">{{ number_format($totals['annual'], 2, ',', '.') }} €</p>
                </div>
                <div class="mv-surface p-5">
                    <p class="text-sm text-ink-500">Média mensal por membro</p>
                    <p class="mt-2 text-2xl font-semibold text-ink-900">{{ number_format($household->members->count() ? $totals['monthly'] / $household->members->count() : 0, 2, ',', '.') }} €</p>
                </div>
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
                            <div class="mt-4 rounded-md bg-ink-50 p-4 text-sm text-ink-600">
                                Sem rendimentos declarados{{ $member->no_income_reason ? ': '.$member->no_income_reason : '.' }}
                            </div>
                        @elseif ($member->incomeRecords->isEmpty())
                            <div class="mt-4 rounded-md border border-dashed border-ink-200 p-4 text-sm text-ink-600">
                                Ainda não declarou rendimentos para este membro.
                            </div>
                        @else
                            <div class="mt-4 grid gap-3 md:grid-cols-2">
                                @foreach ($member->incomeRecords as $record)
                                    <div class="rounded-md border border-ink-100 p-4">
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
                    <div class="mv-surface p-6">
                        <h2 class="font-semibold text-ink-900">Ainda não existem membros no agregado.</h2>
                        <p class="mt-2 text-sm text-ink-600">Adicione primeiro os membros do agregado para poder declarar rendimentos.</p>
                    </div>
                @endforelse
            </section>

            <p class="text-xs leading-5 text-ink-500">Os valores apresentados resultam dos dados declarados e servem apenas para preparação do registo. A elegibilidade será avaliada posteriormente.</p>
        </div>
    </div>
</x-app-layout>
