<x-mv.section>
    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <p class="text-xs font-semibold uppercase text-ink-500">Número da candidatura</p>
            <p class="mt-2 text-lg font-semibold text-ink-900">{{ $application->application_number }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase text-ink-500">Data e hora</p>
            <p class="mt-2 text-lg font-semibold text-ink-900">{{ $application->submitted_at->format('d/m/Y H:i') }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase text-ink-500">Candidato</p>
            <p class="mt-2 font-semibold text-ink-900">{{ $application->adhesionRegistration->full_name }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase text-ink-500">Estado</p>
            <p class="mt-2 font-semibold text-ink-900">{{ $application->status->label() }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase text-ink-500">Concurso</p>
            <p class="mt-2 font-semibold text-ink-900">{{ $application->contest->title }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold uppercase text-ink-500">Programa</p>
            <p class="mt-2 font-semibold text-ink-900">{{ $application->program->name }}</p>
        </div>
    </div>
</x-mv.section>

<x-mv.section title="Resumo submetido">
    <div class="mt-4 grid gap-4 sm:grid-cols-3">
        <x-mv.stat-card
            label="Membros"
            :value="$summary['member_count'] ?? $application->household->members->count()"
        />

        <x-mv.stat-card
            label="Rendimento mensal"
            :value="number_format($summary['monthly_income'] ?? 0, 2, ',', '.') . ' €'"
        />

        <x-mv.stat-card
            label="Documentos"
            :value="$application->applicationDocuments->count()"
        />
    </div>
</x-mv.section>

<x-mv.section title="Habitações pretendidas">
    @if (collect($housingPreferences ?? [])->isNotEmpty())
        <ol class="divide-y divide-ink-100 border-y border-ink-100">
            @foreach ($housingPreferences as $preference)
                <li class="flex flex-wrap items-center justify-between gap-4 py-3 text-sm">
                    <span class="font-semibold text-ink-900">
                        {{ $preference['preference_order'] }}. {{ $preference['public_title'] ?: $preference['public_reference'] ?: 'Habitação selecionada' }}
                    </span>
                    <span class="text-ink-500">
                        {{ $preference['typology'] ?? '—' }}
                        @if ($preference['monthly_rent'] !== null)
                            · {{ number_format((float) $preference['monthly_rent'], 2, ',', ' ') }} €
                        @endif
                    </span>
                </li>
            @endforeach
        </ol>
    @else
        <x-mv.alert>Não existem habitações pretendidas no snapshot da submissão.</x-mv.alert>
    @endif
</x-mv.section>

<x-mv.section title="Documentos associados">
    <div class="mt-4 divide-y divide-ink-100 border-y border-ink-100">
        @foreach ($application->applicationDocuments as $document)
            <div class="flex justify-between gap-4 py-3 text-sm">
                <span class="font-semibold text-ink-900">{{ $document->documentType->name }}</span>
                <x-mv.badge>{{ $document->status_at_submission->label() }}</x-mv.badge>
            </div>
        @endforeach
    </div>
</x-mv.section>
