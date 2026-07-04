<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Vistorias"
            title="Criar template"
            description="Crie um modelo de checklist para reutilização em vistorias."
        />
    </x-slot>

    <form method="POST" action="{{ route('backoffice.inspections.templates.store') }}" class="space-y-6">
        @csrf

        <x-mv.section title="Dados do template">
            <div class="grid gap-4">
                <input class="mv-input" name="code" placeholder="Código" required>
                <input class="mv-input" name="name" placeholder="Nome" required>
                <textarea class="mv-input" name="description" placeholder="Descrição"></textarea>
            </div>
        </x-mv.section>

        <button class="mv-button-primary">Guardar</button>
    </form>
</x-app-layout>
