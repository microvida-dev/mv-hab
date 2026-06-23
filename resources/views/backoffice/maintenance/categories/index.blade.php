<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Categorias de manutenção</h1></x-slot>
    <a class="mv-button-primary" href="{{ route('backoffice.maintenance.categories.create') }}">Criar categoria</a>
    <div class="mv-card mt-4 overflow-x-auto"><table class="min-w-full text-sm"><thead><tr class="text-left text-ink-500"><th>Código</th><th>Nome</th><th>Urgência</th><th></th></tr></thead><tbody>@foreach ($categories as $category)<tr class="border-t border-ink-100"><td class="py-2">{{ $category->code }}</td><td>{{ $category->name }}</td><td>{{ $category->default_urgency?->label() ?? '-' }}</td><td><a class="text-civic-700" href="{{ route('backoffice.maintenance.categories.edit', $category) }}">Editar</a></td></tr>@endforeach</tbody></table>{{ $categories->links() }}</div>
</x-app-layout>
