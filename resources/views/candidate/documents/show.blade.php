<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Documentos"
            title="{{ $submission->documentType->name }}"
            description="{{ $submission->original_filename }}"
        >
            <x-slot name="actions">
                <a href="{{ route('candidate.documents.checklist') }}" class="mv-button-secondary">
                    Voltar à checklist
                </a>

                <a href="{{ route('candidate.documents.download', $submission) }}" class="mv-button-secondary">
                    Download
                </a>

                @can('replace', $submission)
                    <a href="{{ route('candidate.documents.replace.create', $submission) }}" class="mv-button-primary">
                        Substituir
                    </a>
                @endcan
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <x-mv.section
                title="Resumo do documento"
                description="Consulte o estado atual, datas relevantes e versão ativa."
            >
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <p class="text-xs font-semibold uppercase text-ink-500">Estado</p>
                        <p class="mt-1 font-semibold text-ink-900">{{ $submission->status->label() }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase text-ink-500">Submetido em</p>
                        <p class="mt-1 font-semibold text-ink-900">
                            {{ optional($submission->submitted_at)->format('d/m/Y H:i') ?: 'Não indicado' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase text-ink-500">Validade</p>
                        <p class="mt-1 font-semibold text-ink-900">
                            {{ optional($submission->expiry_date)->format('d/m/Y') ?: 'Não indicada' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase text-ink-500">Versão atual</p>
                        <p class="mt-1 font-semibold text-ink-900">
                            {{ $submission->currentVersion?->version_number ?: '-' }}
                        </p>
                    </div>
                </div>

                @if ($submission->rejection_reason)
                    <x-mv.alert tone="danger" class="mt-5">
                        {{ $submission->rejection_reason }}
                    </x-mv.alert>
                @endif
            </x-mv.section>

            <x-mv.section
                title="Histórico de versões"
                description="Todas as versões anteriores permanecem registadas no histórico do processo."
            >
                <div class="divide-y divide-ink-100">
                    @foreach ($submission->versions->sortByDesc('version_number') as $version)
                        <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                            <div>
                                <p class="font-semibold text-ink-900">
                                    Versão {{ $version->version_number }}
                                </p>

                                <p class="text-sm text-ink-500">
                                    {{ $version->original_filename }} · {{ number_format($version->file_size / 1024, 1, ',', '.') }} KB
                                </p>
                            </div>

                            <x-mv.badge>
                                {{ $version->status_at_upload->label() }}
                            </x-mv.badge>
                        </div>
                    @endforeach
                </div>
            </x-mv.section>

            <x-mv.section
                title="Histórico de decisões"
                description="Consulte as decisões administrativas registadas sobre este documento."
            >
                @if ($submission->reviews->isEmpty())
                    <p class="text-sm text-ink-600">
                        Ainda não existem decisões administrativas sobre este documento.
                    </p>
                @else
                    <div class="divide-y divide-ink-100">
                        @foreach ($submission->reviews->sortByDesc('created_at') as $review)
                            <div class="py-3">
                                <p class="font-semibold text-ink-900">
                                    {{ $review->decision->label() }}
                                </p>

                                <p class="text-sm text-ink-500">
                                    {{ $review->created_at->format('d/m/Y H:i') }}
                                </p>

                                @if ($review->reason)
                                    <p class="mt-2 text-sm leading-6 text-ink-700">
                                        {{ $review->reason }}
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-mv.section>
        </div>
    </div>
</x-app-layout>
