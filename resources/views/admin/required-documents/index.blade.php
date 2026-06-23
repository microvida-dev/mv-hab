<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Documentos</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">Regras documentais</h1>
                <p class="mt-1 text-sm text-ink-500">Obrigatoriedade por contexto, condição, programa ou concurso.</p>
            </div>
            <a href="{{ route('admin.required-documents.create') }}" class="mv-button-primary">
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
                                <th class="px-5 py-3">Âmbito</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @foreach ($requiredDocuments as $requiredDocument)
                                <tr>
                                    <td class="px-5 py-4 font-semibold text-ink-900">{{ $requiredDocument->documentType->name }}</td>
                                    <td class="px-5 py-4 text-ink-700">{{ $requiredDocument->required_for->label() }}</td>
                                    <td class="px-5 py-4 text-ink-700">{{ $requiredDocument->condition_key }} · {{ $requiredDocument->condition_operator->label() }}</td>
                                    <td class="px-5 py-4 text-ink-700">{{ $requiredDocument->contest?->title ?? $requiredDocument->program?->name ?? 'Global' }}</td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('admin.required-documents.edit', $requiredDocument) }}" class="mv-button-secondary">Editar</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-ink-100 p-4">{{ $requiredDocuments->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
