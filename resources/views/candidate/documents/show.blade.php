<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-mvhab-primary">Documentos</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $submission->documentType->name }}</h1>
                <p class="mt-1 text-sm text-ink-500">{{ $submission->original_filename }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('candidate.documents.download', $submission) }}" class="mv-button-secondary">
                    Download
                </a>
                @can('replace', $submission)
                    <a href="{{ route('candidate.documents.replace.create', $submission) }}" class="mv-button-primary">
                        Substituir
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <section class="mv-surface p-6">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <p class="text-xs font-semibold uppercase text-ink-500">Estado</p>
                        <p class="mt-1 font-semibold text-ink-900">{{ $submission->status->label() }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase text-ink-500">Submetido em</p>
                        <p class="mt-1 font-semibold text-ink-900">{{ optional($submission->submitted_at)->format('d/m/Y H:i') ?: 'Não indicado' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase text-ink-500">Validade</p>
                        <p class="mt-1 font-semibold text-ink-900">{{ optional($submission->expiry_date)->format('d/m/Y') ?: 'Não indicada' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase text-ink-500">Versão atual</p>
                        <p class="mt-1 font-semibold text-ink-900">{{ $submission->currentVersion?->version_number ?: '-' }}</p>
                    </div>
                </div>

                @if ($submission->rejection_reason)
                    <div class="mt-5 rounded-2xl bg-red-50 p-4 text-sm leading-6 text-red-800">
                        {{ $submission->rejection_reason }}
                    </div>
                @endif
            </section>

            <section class="mv-surface p-6">
                <h2 class="text-base font-semibold text-ink-900">Histórico de versões</h2>
                <div class="mt-4 divide-y divide-ink-100">
                    @foreach ($submission->versions->sortByDesc('version_number') as $version)
                        <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                            <div>
                                <p class="font-semibold text-ink-900">Versão {{ $version->version_number }}</p>
                                <p class="text-sm text-ink-500">{{ $version->original_filename }} · {{ number_format($version->file_size / 1024, 1, ',', '.') }} KB</p>
                            </div>
                            <span class="rounded-2xl bg-ink-100 px-2.5 py-1 text-xs font-semibold text-ink-700">{{ $version->status_at_upload->label() }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="mv-surface p-6">
                <h2 class="text-base font-semibold text-ink-900">Histórico de decisões</h2>
                @if ($submission->reviews->isEmpty())
                    <p class="mt-3 text-sm text-ink-600">Ainda não existem decisões administrativas sobre este documento.</p>
                @else
                    <div class="mt-4 divide-y divide-ink-100">
                        @foreach ($submission->reviews->sortByDesc('created_at') as $review)
                            <div class="py-3">
                                <p class="font-semibold text-ink-900">{{ $review->decision->label() }}</p>
                                <p class="text-sm text-ink-500">{{ $review->created_at->format('d/m/Y H:i') }}</p>
                                @if ($review->reason)
                                    <p class="mt-2 text-sm leading-6 text-ink-700">{{ $review->reason }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
