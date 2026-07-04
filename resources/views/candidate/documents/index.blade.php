<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Documentos"
            title="Documentos submetidos"
            description="Consulte os documentos já enviados e acompanhe o estado de análise."
        >
            <x-slot name="actions">
                <a href="{{ route('candidate.documents.checklist') }}" class="mv-button-primary">
                    <x-ui-icon name="document" class="h-4 w-4" />
                    Checklist documental
                </a>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <x-candidate.registration-stepper :registration="$registration" />

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                @foreach ([
                    'Obrigatórios' => $checklist['summary']['total_required'],
                    'Em falta' => $checklist['summary']['missing'],
                    'Submetidos' => $checklist['summary']['submitted'],
                    'Validados' => $checklist['summary']['validated'],
                    'Rejeitados' => $checklist['summary']['rejected'],
                ] as $label => $value)
                    <x-mv.stat-card
                        :label="$label"
                        :value="$value"
                    />
                @endforeach
            </section>

            <x-mv.section
                title="Histórico documental"
                description="Acompanhe os documentos submetidos, o estado de análise e o detalhe de cada ficheiro."
                padding="none"
            >
                @if ($submissions->isEmpty())
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-ink-900">
                            Ainda não existem documentos submetidos.
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-ink-600">
                            Use a checklist documental para identificar os documentos necessários e iniciar a submissão.
                        </p>
                        <div class="mt-5">
                            <a href="{{ route('candidate.documents.checklist') }}" class="mv-button-primary">
                                Abrir checklist documental
                            </a>
                        </div>
                    </div>
                @else
                    <div class="divide-y divide-ink-100">
                        @foreach ($submissions as $submission)
                            <article class="flex flex-wrap items-center justify-between gap-4 p-5">
                                <div class="min-w-0">
                                    <p class="font-semibold text-ink-900">
                                        {{ $submission->documentType->name }}
                                    </p>
                                    <p class="mt-1 text-sm text-ink-500">
                                        {{ $submission->original_filename ?: 'Sem ficheiro atual' }}
                                    </p>
                                </div>

                                <div class="flex shrink-0 flex-wrap items-center gap-3">
                                    <x-mv.badge>
                                        {{ $submission->status->label() }}
                                    </x-mv.badge>

                                    <a href="{{ route('candidate.documents.show', $submission) }}" class="mv-button-secondary">
                                        Ver detalhe
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="border-t border-ink-100 p-4">
                        {{ $submissions->links() }}
                    </div>
                @endif
            </x-mv.section>
        </div>
    </div>
</x-app-layout>
