<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-civic-700">Procedimento</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Documentos gerados</h1></div></x-slot>
    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="mv-surface overflow-hidden">
                <table class="min-w-full divide-y divide-ink-100 text-sm">
                    <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500"><tr><th class="px-4 py-3">Número</th><th class="px-4 py-3">Título</th><th class="px-4 py-3">Tipo</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3">Gerado em</th><th></th></tr></thead>
                    <tbody class="divide-y divide-ink-100">
                        @forelse ($documents as $document)
                            <tr>
                                <td class="px-4 py-3 font-mono text-xs">{{ $document->document_number }}</td>
                                <td class="px-4 py-3 font-semibold text-ink-900">{{ $document->title }}</td>
                                <td class="px-4 py-3">{{ $document->type->label() }}</td>
                                <td class="px-4 py-3">{{ $document->status->label() }}</td>
                                <td class="px-4 py-3">{{ $document->generated_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="px-4 py-3 text-right"><a class="font-semibold text-civic-700" href="{{ route('backoffice.generated-documents.show', $document) }}">Abrir</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-10 text-center text-ink-500">Sem documentos gerados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $documents->links() }}
        </div>
    </div>
</x-app-layout>
