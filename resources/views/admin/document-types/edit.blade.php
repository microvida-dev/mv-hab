<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Documentos</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Editar tipo documental</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.document-types.update', $documentType) }}" class="mv-surface space-y-6 p-6">
                @csrf
                @method('PATCH')
                @include('admin.document-types.partials.form')
                <div class="flex justify-end gap-3">
                    <a href="{{ route('admin.document-types.index') }}" class="mv-button-secondary">Cancelar</a>
                    <button class="mv-button-primary">Guardar alterações</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
