<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-mvhab-primary">Dossier documental</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $application->application_number ?? 'Candidatura' }}</h1>
                <p class="mt-1 text-sm text-ink-500">Normalização documental para validação interna.</p>
            </div>
            <a href="{{ route('backoffice.applications.show', $application) }}" class="mv-button-secondary">Voltar à candidatura</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-2xl border border-mvhab-support/40 bg-mvhab-surface px-4 py-3 text-sm font-semibold text-mvhab-primary">{{ session('success') }}</div>
            @endif

            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">Gerar dossier</h2>
                <form method="POST" action="{{ route('backoffice.applications.document-dossier.generate', $application) }}" class="mt-5 flex flex-wrap items-center gap-4">
                    @csrf
                    <label class="flex items-center gap-2 text-sm font-semibold text-ink-700"><input type="checkbox" name="include_rejected" value="1" class="rounded border-ink-300"> Incluir rejeitados</label>
                    <label class="flex items-center gap-2 text-sm font-semibold text-ink-700"><input type="checkbox" name="include_expired" value="1" class="rounded border-ink-300"> Incluir expirados</label>
                    <input type="hidden" name="export_format" value="html">
                    <button class="mv-button-primary">Gerar dossier</button>
                </form>
            </section>

            <section class="mv-surface p-6">
                <h2 class="text-lg font-semibold text-ink-900">Dossiers gerados</h2>
                <div class="mt-4 space-y-5">
                    @forelse ($dossiers as $dossier)
                        <article class="rounded-2xl border border-ink-100 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="font-mono text-xs text-ink-500">{{ $dossier->dossier_number }}</p>
                                    <h3 class="mt-1 font-semibold text-ink-900">{{ $dossier->title }}</h3>
                                    <p class="mt-1 text-sm text-ink-500">{{ $dossier->summary }}</p>
                                </div>
                                <div class="text-right text-sm">
                                    <p class="font-semibold text-ink-900">{{ $dossier->status->label() }}</p>
                                    @if ($dossier->file_path)
                                        <a class="mt-2 inline-block font-semibold text-mvhab-primary" href="{{ route('backoffice.document-dossiers.download', $dossier) }}">Download</a>
                                    @endif
                                </div>
                            </div>
                            <div class="mt-4 grid gap-3 sm:grid-cols-4">
                                <div class="rounded-2xl border border-ink-100 bg-mvhab-surface p-3"><p class="text-xs text-ink-500">Validados</p><p class="text-xl font-semibold">{{ $dossier->validated_documents_count }}</p></div>
                                <div class="rounded-2xl border border-ink-100 bg-mvhab-surface p-3"><p class="text-xs text-ink-500">Em falta</p><p class="text-xl font-semibold">{{ $dossier->missing_documents_count }}</p></div>
                                <div class="rounded-2xl border border-ink-100 bg-mvhab-surface p-3"><p class="text-xs text-ink-500">Rejeitados</p><p class="text-xl font-semibold">{{ $dossier->rejected_documents_count }}</p></div>
                                <div class="rounded-2xl border border-ink-100 bg-mvhab-surface p-3"><p class="text-xs text-ink-500">Expirados</p><p class="text-xl font-semibold">{{ $dossier->expired_documents_count }}</p></div>
                            </div>
                            <details class="mt-4">
                                <summary class="cursor-pointer text-sm font-semibold text-mvhab-primary">Ver itens normalizados</summary>
                                <div class="mt-3 overflow-x-auto">
                                    <table class="mv-table">
                                        <thead><tr><th>Documento</th><th>Estado</th><th>Observações</th></tr></thead>
                                        <tbody>
                                            @foreach ($dossier->items as $item)
                                                <tr><td>{{ $item->label }}</td><td>{{ $item->status->label() }}</td><td>{{ $item->notes ?? '—' }}</td></tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </details>
                        </article>
                    @empty
                        <p class="text-sm text-ink-500">Ainda não existem dossiers gerados.</p>
                    @endforelse
                </div>
                <div class="mt-4">{{ $dossiers->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
