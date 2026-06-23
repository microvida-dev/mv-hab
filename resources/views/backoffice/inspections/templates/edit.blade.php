<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Editar template</h1></x-slot>
    <form method="POST" action="{{ route('backoffice.inspections.templates.update', $inspectionChecklistTemplate) }}" class="mv-card grid gap-4">@csrf @method('PATCH')<input class="mv-input" name="code" value="{{ $inspectionChecklistTemplate->code }}" required><input class="mv-input" name="name" value="{{ $inspectionChecklistTemplate->name }}" required><textarea class="mv-input" name="description">{{ $inspectionChecklistTemplate->description }}</textarea><button class="mv-button-primary">Guardar</button></form>
</x-app-layout>
