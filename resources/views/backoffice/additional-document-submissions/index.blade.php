<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">Submissões de documentos adicionais</h1></x-slot>
    <div class="py-8"><div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <section class="mv-surface overflow-hidden">
            <table class="min-w-full divide-y divide-ink-100 text-sm">
                <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500"><tr><th class="px-5 py-3">Documento</th><th class="px-5 py-3">Candidatura</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3"></th></tr></thead>
                <tbody class="divide-y divide-ink-100">@forelse($submissions as $submission)<tr><td class="px-5 py-4 font-semibold">{{ $submission->title }}</td><td class="px-5 py-4">{{ $submission->application?->application_number }}</td><td class="px-5 py-4">{{ $submission->status->label() }}</td><td class="px-5 py-4 text-right"><a class="font-semibold text-civic-700" href="{{ route('backoffice.additional-document-submissions.show', $submission) }}">Analisar</a></td></tr>@empty<tr><td colspan="4" class="px-5 py-8 text-center text-ink-500">Sem submissões.</td></tr>@endforelse</tbody>
            </table>
        </section>
        {{ $submissions->links() }}
    </div></div>
</x-app-layout>
