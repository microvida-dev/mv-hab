<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-ink-900">{{ $household->name }}</h2>
            @if (! $household->isCandidateHousehold())
                <a href="{{ route('households.edit', $household) }}" class="mv-button-secondary">
                    Editar
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <x-flash-message />

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="mv-surface p-6 lg:col-span-2">
                    <h3 class="text-lg font-semibold text-ink-900">Dados do agregado</h3>
                    <dl class="mt-6 grid gap-6 md:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-ink-500">Responsável</dt>
                            <dd class="mt-1 text-sm text-ink-900">
                                {{ $household->citizen?->name ?? $household->adhesionRegistration?->full_name ?? 'Sem responsável associado' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-ink-500">Membros</dt>
                            <dd class="mt-1 text-sm text-ink-900">{{ $household->members_count }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-ink-500">Rendimento mensal</dt>
                            <dd class="mt-1 text-sm text-ink-900">{{ number_format((float) $household->monthly_income, 2, ',', '.') }} €</dd>
                        </div>
                        <div class="md:col-span-2">
                            <dt class="text-sm font-medium text-ink-500">Notas</dt>
                            <dd class="mt-1 text-sm text-ink-900">{{ $household->notes ?: 'Sem notas.' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="mv-surface p-6">
                    <h3 class="text-lg font-semibold text-ink-900">Resumo</h3>
                    <div class="mt-4 rounded-2xl bg-mvhab-surface p-4 text-sm text-ink-600">
                        <p class="font-medium text-ink-900">Candidaturas associadas</p>
                        <p class="mt-1">{{ $household->housingApplications->count() }}</p>
                    </div>
                </div>
            </div>

            <div class="mv-surface p-6">
                <h3 class="text-lg font-semibold text-ink-900">Candidaturas</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($household->housingApplications as $application)
                        <a href="{{ route('applications.show', $application) }}" class="block rounded-2xl border border-ink-100 p-4 hover:bg-mvhab-surface">
                            <p class="font-medium text-ink-900">{{ $application->citizen->name }}</p>
                            <p class="mt-1 text-sm text-ink-500">{{ $application->status->label() }}</p>
                        </a>
                    @empty
                        <p class="text-sm text-ink-500">Sem candidaturas associadas.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
