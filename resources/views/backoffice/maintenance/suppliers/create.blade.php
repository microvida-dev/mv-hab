<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Criar fornecedor</h1></x-slot>
    <form method="POST" action="{{ route('backoffice.maintenance.suppliers.store') }}" class="mv-card grid gap-4">@csrf
        <input class="mv-input" name="name" placeholder="Nome" required><input class="mv-input" name="email" type="email" placeholder="Email institucional"><input class="mv-input" name="phone" placeholder="Telefone"><textarea class="mv-input" name="service_scope" placeholder="Âmbito de serviço"></textarea>
        <button class="mv-button-primary">Guardar</button>
    </form>
</x-app-layout>
