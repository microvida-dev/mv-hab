<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-mvhab-primary">Documentos</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">Documentos submetidos</h1>
                <p class="mt-1 text-sm text-ink-500">Consulte os documentos já enviados e acompanhe o estado de análise.</p>
            </div>
            <a href="{{ route('candidate.documents.checklist') }}" class="mv-button-primary">
                <x-ui-icon name="document" class="h-4 w-4" />
                Checklist documental
            </a>
        </div>
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
                    <div class="mv-surface p-5">
                        <p class="text-sm text-ink-500">{{ $label }}</p>
                        <p class="mt-2 text-2xl font-semibold text-ink-900">{{ $value }}</p>
                    </div>
                @endforeach
            </section>

            <section class="mv-surface overflow-hidden">
                @if ($submissions->isEmpty())
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-ink-900">Ainda não existem documentos submetidos.</h2>
                        <p class="mt-2 text-sm leading-6 text-ink-600">Use a checklist documental para identificar os documentos necessários e iniciar a submissão.</p>
                    </div>
                @else
                    <div class="divide-y divide-ink-100">
                        @foreach ($submissions as $submission)
                            <article class="flex flex-wrap items-center justify-between gap-4 p-5">
                                <div>
                                    <p class="font-semibold text-ink-900">{{ $submission->documentType->name }}</p>
                                    <p class="mt-1 text-sm text-ink-500">{{ $submission->original_filename ?: 'Sem ficheiro atual' }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="rounded-2xl bg-ink-100 px-2.5 py-1 text-xs font-semibold text-ink-700">{{ $submission->status->label() }}</span>
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
            </section>
        </div>
    </div>
</x-app-layout>
