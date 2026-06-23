<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">{{ $checklist->checklist_number }}</h1></x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-flash-message />
        <section class="mv-surface p-5"><p class="text-sm text-ink-500">{{ $checklist->status?->label() }} · {{ $checklist->environment }}</p><form method="POST" action="{{ route('backoffice.security.checklists.approve', $checklist) }}" class="mt-4">@csrf<button class="mv-button-primary">Aprovar checklist</button></form></section>
        <section class="mv-surface overflow-hidden"><table class="mv-table"><thead><tr><th>Categoria</th><th>Estado</th><th>Evidência</th><th></th></tr></thead><tbody>@foreach ($checklist->items as $item)<tr><td>{{ $item->title }}</td><td>{{ $item->status?->label() }}</td><td>{{ $item->evidence }}</td><td><form method="POST" action="{{ route('backoffice.security.checklist-items.update', $item) }}" class="flex gap-2">@csrf @method('PATCH')<select name="status" class="mv-input">@foreach (\App\Enums\SecurityChecklistStatus::options() as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select><input name="evidence" class="mv-input" placeholder="Evidência"><button class="mv-button-secondary">Guardar</button></form></td></tr>@endforeach</tbody></table></section>
    </div></div>
</x-app-layout>
