<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Criar categoria</h1></x-slot>
    <form method="POST" action="{{ route('backoffice.maintenance.categories.store') }}" class="mv-card grid gap-4">@csrf
        <input class="mv-input" name="code" placeholder="Código" required><input class="mv-input" name="name" placeholder="Nome" required><textarea class="mv-input" name="description" placeholder="Descrição"></textarea>
        <button class="mv-button-primary">Guardar</button>
    </form>
</x-app-layout>
