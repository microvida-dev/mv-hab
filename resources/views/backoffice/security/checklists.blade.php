<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">Checklists pré-produção</h1></x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-flash-message />
        <form method="POST" action="{{ route('backoffice.security.checklists.store') }}" class="mv-surface flex flex-wrap items-end gap-4 p-5">@csrf<label class="grid gap-1 text-sm"><span>Ambiente</span><input name="environment" value="pre-production" class="mv-input"></label><button class="mv-button-primary">Criar checklist</button></form>
        <section class="mv-surface overflow-hidden"><table class="mv-table"><thead><tr><th>Número</th><th>Estado</th><th>Ambiente</th><th></th></tr></thead><tbody>@foreach ($checklists as $checklist)<tr><td>{{ $checklist->checklist_number }}</td><td>{{ $checklist->status?->label() }}</td><td>{{ $checklist->environment }}</td><td><a class="mv-link" href="{{ route('backoffice.security.checklists.show', $checklist) }}">Ver</a></td></tr>@endforeach</tbody></table><div class="p-4">{{ $checklists->links() }}</div></section>
    </div></div>
</x-app-layout>
