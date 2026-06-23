<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Documentos</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Editar regra documental</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.required-documents.update', $requiredDocument) }}" class="mv-surface space-y-6 p-6">
                @csrf
                @method('PATCH')
                @include('admin.required-documents.partials.form')
                <div class="flex justify-end gap-3">
                    <a href="{{ route('admin.required-documents.index') }}" class="mv-button-secondary">Cancelar</a>
                    <button class="mv-button-primary">Guardar alterações</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
