<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Documentos</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Revisão documental</h1>
            <p class="mt-1 text-sm text-ink-500">Fila de documentos submetidos pelos candidatos.</p>
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
                                <th class="px-5 py-3">Candidato</th>
                                <th class="px-5 py-3">Documento</th>
                                <th class="px-5 py-3">Estado</th>
                                <th class="px-5 py-3">Submissão</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @foreach ($submissions as $submission)
                                <tr>
                                    <td class="px-5 py-4 text-ink-700">{{ $submission->adhesionRegistration?->full_name ?? $submission->adhesionRegistration?->user?->name ?? 'Não indicado' }}</td>
                                    <td class="px-5 py-4">
                                        <p class="font-semibold text-ink-900">{{ $submission->documentType->name }}</p>
                                        <p class="text-xs text-ink-500">{{ $submission->original_filename }}</p>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="rounded-md bg-ink-100 px-2.5 py-1 text-xs font-semibold text-ink-700">{{ $submission->status->label() }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-ink-700">{{ optional($submission->submitted_at)->format('d/m/Y H:i') }}</td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('admin.document-reviews.show', $submission) }}" class="mv-button-secondary">Analisar</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-ink-100 p-4">{{ $submissions->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
