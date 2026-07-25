<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">
                    Documentos
                </p>

                <h1 class="mt-1 text-2xl font-semibold text-ink-900">
                    Regras documentais
                </h1>

                <p class="mt-1 text-sm text-ink-500">
                    Obrigatoriedade por contexto, condição, programa ou concurso.
                </p>
            </div>

            <a
                href="{{ route('admin.required-documents.create') }}"
                class="mv-button-primary"
            >
                <x-ui-icon name="plus" class="h-4 w-4" />
                Nova regra
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <section class="mv-surface overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-ink-100 text-sm">
                        <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                            <tr>
                                <th class="px-5 py-3">Documento</th>
                                <th class="px-5 py-3">Contexto</th>
                                <th class="px-5 py-3">Condição</th>
                                <th class="px-5 py-3">Submissões</th>
                                <th class="px-5 py-3">Periodicidade</th>
                                <th class="px-5 py-3">Âmbito</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-ink-100">
                            @forelse ($requiredDocuments as $requiredDocument)
                                <tr>
                                    <td class="px-5 py-4 font-semibold text-ink-900">
                                        {{ $requiredDocument->documentType->name }}
                                    </td>

                                    <td class="px-5 py-4 text-ink-700">
                                        {{ $requiredDocument->required_for->label() }}
                                    </td>

                                    <td class="px-5 py-4 text-ink-700">
                                        <div class="font-medium text-ink-900">
                                            {{ $requiredDocument->condition_key }}
                                        </div>

                                        <div class="mt-1 text-xs text-ink-500">
                                            {{ $requiredDocument->condition_operator->label() }}

                                            @if (filled($requiredDocument->condition_value))
                                                · {{ $requiredDocument->condition_value }}
                                            @endif
                                        </div>
                                    </td>

                                    <td class="px-5 py-4 text-ink-700">
                                        <div class="font-medium text-ink-900">
                                            {{ $requiredDocument->required_submissions }}
                                        </div>

                                        <div class="mt-1 text-xs text-ink-500">
                                            {{ $requiredDocument->required_submissions === 1
                                                ? 'documento'
                                                : 'documentos' }}
                                        </div>
                                    </td>

                                    <td class="px-5 py-4 text-ink-700">
                                        @if ($requiredDocument->reference_period_unit)
                                            <div class="font-medium text-ink-900">
                                                {{ $requiredDocument->reference_period_unit->label() }}
                                            </div>

                                            <div class="mt-1 text-xs text-ink-500">
                                                @if ($requiredDocument->requires_distinct_reference_periods)
                                                    Períodos distintos
                                                @else
                                                    Períodos repetíveis
                                                @endif

                                                @if ($requiredDocument->reference_period_recency)
                                                    · até
                                                    {{ $requiredDocument->reference_period_recency }}
                                                    período(s) anteriores
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-ink-500">
                                                Não periódico
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-5 py-4 text-ink-700">
                                        <div class="font-medium text-ink-900">
                                            {{ $requiredDocument->contest?->title
                                                ?? $requiredDocument->program?->name
                                                ?? 'Global' }}
                                        </div>

                                        <div class="mt-1 text-xs text-ink-500">
                                            @if ($requiredDocument->contest_id)
                                                Concurso
                                            @elseif ($requiredDocument->program_id)
                                                Programa
                                            @else
                                                Regra global
                                            @endif
                                        </div>
                                    </td>

                                    <td class="px-5 py-4 text-right">
                                        <a
                                            href="{{ route(
                                                'admin.required-documents.edit',
                                                $requiredDocument,
                                            ) }}"
                                            class="mv-button-secondary"
                                        >
                                            Editar
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="7"
                                        class="px-5 py-12 text-center text-sm text-ink-500"
                                    >
                                        Não existem regras documentais configuradas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-ink-100 p-4">
                    {{ $requiredDocuments->links() }}
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
