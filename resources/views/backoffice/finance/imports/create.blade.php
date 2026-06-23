<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Importar CSV</h1></x-slot>
    <form method="POST" enctype="multipart/form-data" action="{{ route('backoffice.finance.imports.store') }}" class="mv-card grid gap-4">@csrf<input class="mv-input" type="file" name="file" accept=".csv,.txt" required><textarea class="mv-input" name="notes" placeholder="Notas"></textarea><button class="mv-button-primary">Importar</button></form>
</x-app-layout>
