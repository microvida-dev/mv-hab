<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Manutenção"
            title="Criar categoria"
            description="Crie uma categoria para classificar pedidos de manutenção."
        />
    </x-slot>

    <form method="POST" action="{{ route('backoffice.maintenance.categories.store') }}" class="space-y-6">
        @csrf

        <x-mv.section title="Dados da categoria">
            <div class="grid gap-4">
                <input class="mv-input" name="code" placeholder="Código" required>
                <input class="mv-input" name="name" placeholder="Nome" required>
                <textarea class="mv-input" name="description" placeholder="Descrição"></textarea>
            </div>
        </x-mv.section>

        <button class="mv-button-primary">Guardar</button>
    </form>
</x-app-layout>
