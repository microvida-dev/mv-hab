<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Documentos</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">Tipos documentais</h1>
                <p class="mt-1 text-sm text-ink-500">Catálogo configurável de documentos aceites pela plataforma.</p>
            </div>
            <a href="{{ route('admin.document-types.create') }}" class="mv-button-primary">
                <x-ui-icon name="plus" class="h-4 w-4" />
                Novo tipo
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
                                <th class="px-5 py-3">Tipo</th>
                                <th class="px-5 py-3">Categoria</th>
                                <th class="px-5 py-3">Aplica-se a</th>
                                <th class="px-5 py-3">Estado</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @foreach ($documentTypes as $documentType)
                                <tr>
                                    <td class="px-5 py-4">
                                        <p class="font-semibold text-ink-900">{{ $documentType->name }}</p>
                                        <p class="text-xs text-ink-500">{{ $documentType->code }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-ink-700">{{ $documentType->category->label() }}</td>
                                    <td class="px-5 py-4 text-ink-700">{{ $documentType->applies_to->label() }}</td>
                                    <td class="px-5 py-4">
                                        <span class="rounded-md bg-ink-100 px-2.5 py-1 text-xs font-semibold text-ink-700">{{ $documentType->is_active ? 'Ativo' : 'Inativo' }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('admin.document-types.edit', $documentType) }}" class="mv-button-secondary">Editar</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-ink-100 p-4">{{ $documentTypes->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
