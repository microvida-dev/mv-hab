<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Documentos</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Revisão documental</h1>
            <p class="mt-1 text-sm text-ink-500">
                Fila agrupada por processo para reduzir duplicações visuais e acelerar a análise técnica.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <section class="mv-surface px-5 py-5">
                <form method="GET" action="{{ route('admin.document-reviews.index') }}" class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div class="xl:col-span-2">
                            <label for="search" class="text-xs font-semibold uppercase tracking-wide text-ink-500">
                                Pesquisa
                            </label>
                            <input
                                id="search"
                                name="search"
                                type="search"
                                value="{{ $filters['search'] }}"
                                placeholder="Candidato, membro, NIF, email, ficheiro..."
                                class="mt-1 w-full rounded-2xl border-ink-200 text-sm shadow-sm focus:border-mvhab-primary focus:ring-mvhab-primary"
                            >
                        </div>

                        <div>
                            <label for="status" class="text-xs font-semibold uppercase tracking-wide text-ink-500">
                                Estado
                            </label>
                            <select
                                id="status"
                                name="status"
                                class="mt-1 w-full rounded-2xl border-ink-200 text-sm shadow-sm focus:border-mvhab-primary focus:ring-mvhab-primary"
                            >
                                <option value="">Todos</option>
                                @foreach ($statusOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($filters['status'] === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="document_type_id" class="text-xs font-semibold uppercase tracking-wide text-ink-500">
                                Tipo de documento
                            </label>
                            <select
                                id="document_type_id"
                                name="document_type_id"
                                class="mt-1 w-full rounded-2xl border-ink-200 text-sm shadow-sm focus:border-mvhab-primary focus:ring-mvhab-primary"
                            >
                                <option value="">Todos</option>
                                @foreach ($documentTypes as $documentType)
                                    <option value="{{ $documentType->id }}" @selected($filters['document_type_id'] === (string) $documentType->id)>
                                        {{ $documentType->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="context" class="text-xs font-semibold uppercase tracking-wide text-ink-500">
                                Contexto
                            </label>
                            <select
                                id="context"
                                name="context"
                                class="mt-1 w-full rounded-2xl border-ink-200 text-sm shadow-sm focus:border-mvhab-primary focus:ring-mvhab-primary"
                            >
                                <option value="">Todos</option>
                                <option value="registration" @selected($filters['context'] === 'registration')>Registo</option>
                                <option value="application" @selected($filters['context'] === 'application')>Candidatura</option>
                            </select>
                        </div>

                        <div>
                            <label for="member_state" class="text-xs font-semibold uppercase tracking-wide text-ink-500">
                                Elemento do agregado
                            </label>
                            <select
                                id="member_state"
                                name="member_state"
                                class="mt-1 w-full rounded-2xl border-ink-200 text-sm shadow-sm focus:border-mvhab-primary focus:ring-mvhab-primary"
                            >
                                <option value="">Todos</option>
                                <option value="associated" @selected($filters['member_state'] === 'associated')>Com elemento associado</option>
                                <option value="missing" @selected($filters['member_state'] === 'missing')>Sem elemento associado</option>
                            </select>
                        </div>

                        <div>
                            <label for="date_from" class="text-xs font-semibold uppercase tracking-wide text-ink-500">
                                Submetido desde
                            </label>
                            <input
                                id="date_from"
                                name="date_from"
                                type="date"
                                value="{{ $filters['date_from'] }}"
                                class="mt-1 w-full rounded-2xl border-ink-200 text-sm shadow-sm focus:border-mvhab-primary focus:ring-mvhab-primary"
                            >
                        </div>

                        <div>
                            <label for="date_to" class="text-xs font-semibold uppercase tracking-wide text-ink-500">
                                Submetido até
                            </label>
                            <input
                                id="date_to"
                                name="date_to"
                                type="date"
                                value="{{ $filters['date_to'] }}"
                                class="mt-1 w-full rounded-2xl border-ink-200 text-sm shadow-sm focus:border-mvhab-primary focus:ring-mvhab-primary"
                            >
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-ink-100 pt-4">
                        <p class="text-sm text-ink-500">
                            {{ $submissions->total() }} documento(s) encontrados.
                        </p>

                        <div class="flex flex-wrap items-center gap-2">
                            @if ($hasActiveFilters)
                                <a href="{{ route('admin.document-reviews.index') }}" class="mv-button-secondary">
                                    Limpar filtros
                                </a>
                            @endif

                            <button type="submit" class="mv-button-primary">
                                Filtrar
                            </button>
                        </div>
                    </div>
                </form>
            </section>

            <section class="space-y-4">
                @forelse ($documentGroups as $group)
                    <details
                        class="group mv-surface overflow-hidden"
                        @if ($group['is_open']) open @endif
                    >
                        <summary class="flex cursor-pointer list-none flex-col gap-4 px-5 py-5 hover:bg-ink-50/70 lg:flex-row lg:items-center lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-base font-semibold text-ink-900">
                                        {{ $group['candidate_name'] }}
                                    </h2>

                                    @if ($group['application_id'])
                                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                            Candidatura #{{ $group['application_id'] }}
                                        </span>
                                    @elseif ($group['registration_id'])
                                        <span class="rounded-full bg-ink-100 px-3 py-1 text-xs font-semibold text-ink-700">
                                            Registo #{{ $group['registration_id'] }}
                                        </span>
                                    @else
                                        <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
                                            Sem processo associado
                                        </span>
                                    @endif
                                </div>

                                <p class="mt-1 text-sm text-ink-500">
                                    @if ($group['candidate_email'])
                                        {{ $group['candidate_email'] }} ·
                                    @endif

                                    {{ $group['total'] }} documento(s)

                                    @if ($group['last_submission_at'])
                                        · última submissão {{ $group['last_submission_at']->format('d/m/Y H:i') }}
                                    @endif
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                @foreach ($group['status_counts'] as $status)
                                    @php
                                        $statusClass = match ($status['value']) {
                                            'validated' => 'bg-green-50 text-green-700',
                                            'rejected' => 'bg-red-50 text-red-700',
                                            'under_review' => 'bg-blue-50 text-blue-700',
                                            default => 'bg-ink-100 text-ink-700',
                                        };
                                    @endphp

                                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">
                                        {{ $status['count'] }} {{ $status['label'] }}
                                    </span>
                                @endforeach

                                <span
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-ink-50 text-ink-500 transition group-hover:bg-ink-100"
                                    aria-hidden="true"
                                >
                                    <svg
                                        class="h-4 w-4 transition-transform duration-200 group-open:-rotate-180"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </span>

                                <span class="sr-only">
                                    Abrir ou recolher grupo documental
                                </span>
                            </div>
                        </summary>

                        <div class="divide-y divide-ink-100 border-t border-ink-100">
                            @foreach ($group['documents'] as $document)
                                @php
                                    $submission = $document['submission'];

                                    $documentStatusClass = match ($document['status_value']) {
                                        'validated' => 'bg-green-50 text-green-700',
                                        'rejected' => 'bg-red-50 text-red-700',
                                        'under_review' => 'bg-blue-50 text-blue-700',
                                        default => 'bg-ink-100 text-ink-700',
                                    };
                                @endphp

                                <article class="grid gap-4 px-5 py-5 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-sm font-semibold text-ink-900">
                                                {{ $document['requirement_label'] }}
                                            </h3>

                                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $documentStatusClass }}">
                                                {{ $document['status_label'] }}
                                            </span>
                                        </div>

                                        <p class="mt-1 break-all text-xs text-ink-500">
                                            {{ $submission->original_filename }}
                                        </p>

                                        <dl class="mt-4 grid gap-3 text-xs sm:grid-cols-2 xl:grid-cols-4">
                                            <div>
                                                <dt class="font-semibold uppercase tracking-wide text-ink-400">
                                                    Elemento
                                                </dt>
                                                <dd class="mt-1 text-ink-700">
                                                    {{ $document['member_name'] }}

                                                    <span class="mt-0.5 block text-[11px] font-medium text-ink-400">
                                                        {{ $document['member_hint'] }}
                                                    </span>
                                                </dd>
                                            </div>

                                            <div>
                                                <dt class="font-semibold uppercase tracking-wide text-ink-400">
                                                    Requisito
                                                </dt>
                                                <dd class="mt-1 text-ink-700">
                                                    {{ $document['requirement_label'] }}
                                                </dd>
                                            </div>

                                            <div>
                                                <dt class="font-semibold uppercase tracking-wide text-ink-400">
                                                    Contexto
                                                </dt>
                                                <dd class="mt-1 text-ink-700">
                                                    {{ $document['context_label'] }}
                                                </dd>
                                            </div>

                                            <div>
                                                <dt class="font-semibold uppercase tracking-wide text-ink-400">
                                                    Campo-chave
                                                </dt>
                                                <dd class="mt-1 text-ink-700">
                                                    {{ $document['key_field'] }}
                                                </dd>
                                            </div>
                                        </dl>
                                    </div>

                                    <div class="flex justify-end">
                                        <a
                                            href="{{ route('admin.document-reviews.show', $submission) }}"
                                            class="mv-button-secondary"
                                        >
                                            Analisar
                                        </a>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </details>
                @empty
                    <section class="mv-surface px-6 py-12 text-center">
                        <p class="text-sm font-semibold text-ink-900">
                            Sem documentos para revisão
                        </p>
                        <p class="mt-1 text-sm text-ink-500">
                            Não existem documentos submetidos para apresentar nesta fila.
                        </p>
                    </section>
                @endforelse
            </section>

            <div>
                {{ $submissions->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
