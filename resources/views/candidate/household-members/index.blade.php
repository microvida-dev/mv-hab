<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Agregado familiar"
            title="Membros do agregado"
            :description="$household->members->count() . ' membro(s) registado(s)'"
        >
            <x-slot name="actions">
                <a href="{{ route('candidate.household-members.create') }}" class="mv-button-primary">
                    <x-ui-icon name="plus" class="h-4 w-4" />
                    Adicionar membro
                </a>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <x-candidate.registration-stepper :registration="$household->adhesionRegistration->loadMissing(['household.members.incomeRecords', 'currentHousingSituation'])" />

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            <x-input-error :messages="$errors->get('member')" />

            <div class="grid gap-4 sm:grid-cols-3">
                <x-mv.stat-card
                    label="Membros"
                    :value="$household->members->count()"
                />
                <x-mv.stat-card
                    label="Dependentes"
                    :value="$household->members->where('is_dependent', true)->count()"
                />
                <x-mv.stat-card
                    label="Rendimento mensal"
                    :value="number_format((float) $household->monthly_income, 2, ',', '.') . ' €'"
                />
            </div>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($household->members as $member)
                    <article class="mv-surface flex flex-col p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="font-semibold text-ink-900">{{ $member->full_name }}</h2>
                                <p class="mt-1 text-sm text-ink-500">{{ $member->relationship->label() }}</p>
                            </div>
                            @if ($member->is_applicant)
                                <x-mv.badge tone="success">Requerente</x-mv.badge>
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
                    <x-mv.alert class="md:col-span-2 xl:col-span-3">
                        <strong>Ainda não adicionou elementos ao agregado.</strong>
                        <span class="mt-1 block">Adicione os elementos que vivem consigo ou que fazem parte da sua futura candidatura habitacional.</span>
                    </x-mv.alert>
                @endforelse
            </section>

            <a href="{{ route('candidate.household.show') }}" class="mv-button-secondary">Voltar ao resumo</a>
        </div>
    </div>
</x-app-layout>
