<section class="mv-surface p-6">
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
</section>

<section class="mv-surface p-6">
    <h2 class="text-lg font-semibold text-ink-900">Resumo submetido</h2>
    <div class="mt-4 grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-ink-100 p-4">
            <p class="text-xs uppercase text-ink-500">Membros</p>
            <p class="mt-2 text-xl font-semibold text-ink-900">{{ $summary['member_count'] ?? $application->household->members->count() }}</p>
        </div>
        <div class="rounded-2xl border border-ink-100 p-4">
            <p class="text-xs uppercase text-ink-500">Rendimento mensal</p>
            <p class="mt-2 text-xl font-semibold text-ink-900">{{ number_format($summary['monthly_income'] ?? 0, 2, ',', '.') }} €</p>
        </div>
        <div class="rounded-2xl border border-ink-100 p-4">
            <p class="text-xs uppercase text-ink-500">Documentos</p>
            <p class="mt-2 text-xl font-semibold text-ink-900">{{ $application->applicationDocuments->count() }}</p>
        </div>
    </div>
</section>

<section class="mv-surface p-6">
    <h2 class="text-lg font-semibold text-ink-900">Documentos associados</h2>
    <div class="mt-4 divide-y divide-ink-100 border-y border-ink-100">
        @foreach ($application->applicationDocuments as $document)
            <div class="flex justify-between gap-4 py-3 text-sm">
                <span class="font-semibold text-ink-900">{{ $document->documentType->name }}</span>
                <span class="text-ink-500">{{ $document->status_at_submission->label() }}</span>
            </div>
        @endforeach
    </div>
</section>
