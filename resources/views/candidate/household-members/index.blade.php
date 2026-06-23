<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Agregado familiar</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">Membros do agregado</h1>
                <p class="mt-1 text-sm text-ink-500">{{ $household->members->count() }} membro(s) registado(s)</p>
            </div>
            <a href="{{ route('candidate.household-members.create') }}" class="mv-button-primary">
                <x-ui-icon name="plus" class="h-4 w-4" />
                Adicionar membro
            </a>
        </div>
    </x-slot>

    <x-candidate.registration-stepper :registration="$household->adhesionRegistration->loadMissing(['household.members.incomeRecords', 'currentHousingSituation'])" />

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            <x-input-error :messages="$errors->get('member')" />

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($household->members as $member)
                    <article class="mv-surface flex flex-col p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="font-semibold text-ink-900">{{ $member->full_name }}</h2>
                                <p class="mt-1 text-sm text-ink-500">{{ $member->relationship->label() }}</p>
                            </div>
                            @if ($member->is_applicant)
                                <span class="rounded-md bg-civic-50 px-2 py-1 text-xs font-semibold text-civic-700">Requerente</span>
                            @endif
                        </div>

                        <dl class="mt-5 grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <dt class="text-ink-500">Idade</dt>
                                <dd class="mt-1 font-semibold text-ink-900">{{ $member->age() ?? 'Por indicar' }}</dd>
                            </div>
                            <div>
                                <dt class="text-ink-500">Rendimento mensal</dt>
                                <dd class="mt-1 font-semibold text-ink-900">{{ number_format((float) $member->monthly_declared_income, 2, ',', '.') }} €</dd>
                            </div>
                        </dl>

                        <div class="mt-auto flex flex-wrap gap-2 pt-6">
                            <a href="{{ route('candidate.household-members.edit', $member) }}" class="mv-button-secondary">Editar</a>
                            @if (! $member->is_applicant)
                                <form method="POST" action="{{ route('candidate.household-members.destroy', $member) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="mv-button-danger" onclick="return confirm('Remover este membro e os rendimentos associados?')">Remover</button>
                                </form>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="mv-surface p-6 md:col-span-2 xl:col-span-3">
                        <h2 class="font-semibold text-ink-900">Ainda não adicionou elementos ao agregado.</h2>
                        <p class="mt-2 text-sm leading-6 text-ink-600">Adicione os elementos que vivem consigo ou que fazem parte da sua futura candidatura habitacional.</p>
                    </div>
                @endforelse
            </section>

            <a href="{{ route('candidate.household.show') }}" class="mv-button-secondary">Voltar ao resumo</a>
        </div>
    </div>
</x-app-layout>
