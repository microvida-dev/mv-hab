<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Documentos"
            title="Submissões de documentos adicionais"
            description="Analise documentos submetidos em resposta a pedidos adicionais ou complementares."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <x-mv.section title="Submissões" padding="p-0" class="overflow-hidden">
                <table class="min-w-full divide-y divide-ink-100 text-sm">
                    <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                        <tr>
                            <th class="px-5 py-3">Documento</th>
                            <th class="px-5 py-3">Candidatura</th>
                            <th class="px-5 py-3">Estado</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @forelse ($submissions as $submission)
                            <tr>
                                <td class="px-5 py-4 font-semibold">{{ $submission->title }}</td>
                                <td class="px-5 py-4">{{ $submission->application?->application_number }}</td>
                                <td class="px-5 py-4"><x-mv.badge>{{ $submission->status->label() }}</x-mv.badge></td>
                                <td class="px-5 py-4 text-right">
                                    <a class="font-semibold text-civic-700" href="{{ route('backoffice.additional-document-submissions.show', $submission) }}">Analisar</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-8 text-center text-ink-500">Sem submissões.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </x-mv.section>

            {{ $submissions->links() }}
        </div>
    </div>
</x-app-layout>
