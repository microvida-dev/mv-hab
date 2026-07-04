<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Vistorias"
            title="Editar template"
            :description="$inspectionChecklistTemplate->name"
        />
    </x-slot>

    <form method="POST" action="{{ route('backoffice.inspections.templates.update', $inspectionChecklistTemplate) }}" class="space-y-6">
        @csrf
        @method('PATCH')

        <x-mv.section title="Dados do template">
            <div class="grid gap-4">
                <input class="mv-input" name="code" value="{{ $inspectionChecklistTemplate->code }}" required>
                <input class="mv-input" name="name" value="{{ $inspectionChecklistTemplate->name }}" required>
                <textarea class="mv-input" name="description">{{ $inspectionChecklistTemplate->description }}</textarea>
            </div>
        </x-mv.section>

        <button class="mv-button-primary">Guardar</button>
    </form>
</x-app-layout>
