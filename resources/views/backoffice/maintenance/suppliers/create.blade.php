<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Manutenção"
            title="Criar fornecedor"
            description="Registe um fornecedor de serviços de manutenção."
        />
    </x-slot>

    <form method="POST" action="{{ route('backoffice.maintenance.suppliers.store') }}" class="space-y-6">
        @csrf

        <x-mv.section title="Dados do fornecedor">
            <div class="grid gap-4">
                <input class="mv-input" name="name" placeholder="Nome" required>
                <input class="mv-input" name="email" type="email" placeholder="Email institucional">
                <input class="mv-input" name="phone" placeholder="Telefone">
                <textarea class="mv-input" name="service_scope" placeholder="Âmbito de serviço"></textarea>
            </div>
        </x-mv.section>

        <button class="mv-button-primary">Guardar</button>
    </form>
</x-app-layout>
