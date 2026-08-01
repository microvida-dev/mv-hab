<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Aperfeiçoamentos"
            title="Submetidos para segunda análise"
            description="Fila municipal de revalidação diferencial, limitada ao contexto autorizado."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
                <x-mv.stat-card label="Total" :value="$summary['total']" />
                <x-mv.stat-card label="Por iniciar" :value="$summary['awaiting_review']" />
                <x-mv.stat-card label="Em análise" :value="$summary['in_review']" />
                <x-mv.stat-card label="Selados" :value="$summary['sealed']" />
                <x-mv.stat-card label="Publicados" :value="$summary['published']" />
                <x-mv.stat-card label="Resolvidos" :value="$summary['resolved']" />
            </div>

            <x-mv.section
                title="Filtrar fila"
                description="O Município é aplicado pelo âmbito do utilizador antes da paginação."
            >
                <form method="GET" action="{{ route('backoffice.correction-revalidations.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label for="contest_id" class="text-sm font-semibold text-ink-800">Concurso</label>
                        <select id="contest_id" name="contest_id" class="mt-1 block w-full rounded-md border-ink-200 text-sm">
                            <option value="">Todos</option>
                            @foreach ($contests as $contest)
                                <option value="{{ $contest->id }}" @selected($filters['contest_id'] === $contest->id)>
                                    {{ $contest->code }} · {{ $contest->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="state" class="text-sm font-semibold text-ink-800">Estado da segunda análise</label>
                        <select id="state" name="state" class="mt-1 block w-full rounded-md border-ink-200 text-sm">
                            <option value="">Todos</option>
                            <option value="awaiting_review" @selected($filters['state'] === 'awaiting_review')>Por iniciar</option>
                            <option value="in_review" @selected($filters['state'] === 'in_review')>Em análise</option>
                            <option value="ready_to_seal" @selected($filters['state'] === 'ready_to_seal')>Pronto para selar</option>
                            <option value="sealed" @selected($filters['state'] === 'sealed')>Selado</option>
                            <option value="published" @selected($filters['state'] === 'published')>Publicado</option>
                            <option value="resolved" @selected($filters['state'] === 'resolved')>Resolvido</option>
                        </select>
                    </div>

                    <div>
                        <label for="result" class="text-sm font-semibold text-ink-800">Resultado</label>
                        <select id="result" name="result" class="mt-1 block w-full rounded-md border-ink-200 text-sm">
                            <option value="">Todos</option>
                            @foreach (\App\Enums\CorrectionRevalidationAggregateResult::options() as $value => $label)
                                <option value="{{ $value }}" @selected($filters['result'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="technician_id" class="text-sm font-semibold text-ink-800">Técnico</label>
                        <select id="technician_id" name="technician_id" class="mt-1 block w-full rounded-md border-ink-200 text-sm">
                            <option value="">Todos</option>
                            @foreach ($technicians as $technician)
                                <option value="{{ $technician->id }}" @selected($filters['technician_id'] === $technician->id)>{{ $technician->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="submitted_from" class="text-sm font-semibold text-ink-800">Submetido desde</label>
                        <input id="submitted_from" type="date" name="submitted_from" value="{{ $filters['submitted_from'] }}" class="mt-1 block w-full rounded-md border-ink-200 text-sm">
                    </div>

                    <div>
                        <label for="submitted_to" class="text-sm font-semibold text-ink-800">Submetido até</label>
                        <input id="submitted_to" type="date" name="submitted_to" value="{{ $filters['submitted_to'] }}" class="mt-1 block w-full rounded-md border-ink-200 text-sm">
                    </div>

                    <div>
                        <label for="sla" class="text-sm font-semibold text-ink-800">Prazo processual</label>
                        <select id="sla" name="sla" class="mt-1 block w-full rounded-md border-ink-200 text-sm">
                            <option value="">Todos</option>
                            <option value="overdue" @selected($filters['sla'] === 'overdue')>Ultrapassado</option>
                            <option value="due_soon" @selected($filters['sla'] === 'due_soon')>A terminar em 48 horas</option>
                            <option value="within_deadline" @selected($filters['sla'] === 'within_deadline')>Dentro do prazo</option>
                        </select>
                    </div>

                    <div>
                        <label for="process_number" class="text-sm font-semibold text-ink-800">Número de processo</label>
                        <input id="process_number" name="process_number" value="{{ $filters['process_number'] }}" class="mt-1 block w-full rounded-md border-ink-200 text-sm">
                    </div>

                    <div>
                        <label for="application_number" class="text-sm font-semibold text-ink-800">Número de candidatura</label>
                        <input id="application_number" name="application_number" value="{{ $filters['application_number'] }}" class="mt-1 block w-full rounded-md border-ink-200 text-sm">
                    </div>

                    <div class="flex flex-wrap items-end gap-2 md:col-span-2 xl:col-span-3">
                        <button type="submit" class="mv-button-primary">Aplicar filtros</button>
                        <a href="{{ route('backoffice.correction-revalidations.index') }}" class="mv-button-secondary">Limpar</a>
                    </div>
                </form>
            </x-mv.section>

            <div class="mv-surface overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-ink-100 text-sm">
                        <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                            <tr>
                                <th class="px-5 py-3">Pedido</th>
                                <th class="px-5 py-3">Processo</th>
                                <th class="px-5 py-3">Candidatura</th>
                                <th class="px-5 py-3">Submissão</th>
                                <th class="px-5 py-3">Progresso</th>
                                <th class="px-5 py-3">Estado</th>
                                <th class="px-5 py-3"><span class="sr-only">Ações</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @forelse ($requests as $correctionRequest)
                                @php
                                    $isPublished = $correctionRequest->revalidation_publication_result_id !== null;
                                    $isSealed = $correctionRequest->revalidationBatch !== null;
                                    $isStarted = $correctionRequest->revalidation_started_at !== null;
                                    $reviewed = (int) $correctionRequest->reviewed_responses_count;
                                    $total = (int) $correctionRequest->responses_count;
                                    $stateLabel = $correctionRequest->status === \App\Enums\CorrectionRequestStatus::Resolved
                                        ? 'Resolvido'
                                        : ($isPublished
                                            ? 'Publicado'
                                            : ($isSealed
                                                ? 'Selado'
                                                : ($isStarted ? 'Em análise' : 'Por iniciar')));
                                    $stateTone = $correctionRequest->status === \App\Enums\CorrectionRequestStatus::Resolved
                                        ? 'success'
                                        : ($isPublished || $isSealed ? 'warning' : 'neutral');
                                @endphp
                                <tr>
                                    <td class="px-5 py-4 font-semibold text-ink-900">{{ $correctionRequest->request_number }}</td>
                                    <td class="px-5 py-4 text-ink-700">{{ $correctionRequest->administrativeProcess?->process_number ?? '—' }}</td>
                                    <td class="px-5 py-4 text-ink-700">{{ $correctionRequest->application?->application_number ?? '—' }}</td>
                                    <td class="px-5 py-4 text-ink-600">{{ $correctionRequest->submitted_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="px-5 py-4 text-ink-600">{{ $reviewed }} / {{ $total }}</td>
                                    <td class="px-5 py-4">
                                        <x-mv.badge :tone="$stateTone">{{ $stateLabel }}</x-mv.badge>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('backoffice.correction-requests.show', $correctionRequest) }}" class="font-semibold text-mvhab-primary hover:underline">Abrir</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-10 text-center text-ink-500">Não existem aperfeiçoamentos submetidos para os filtros selecionados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $requests->links() }}
        </div>
    </div>
</x-app-layout>
