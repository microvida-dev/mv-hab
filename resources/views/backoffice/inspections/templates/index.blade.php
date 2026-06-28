<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Modelos de checklist</h1></x-slot>
    <a class="mv-button-primary" href="{{ route('backoffice.inspections.templates.create') }}">Criar template</a>
    <div class="mv-card mt-4 overflow-x-auto"><table class="min-w-full text-sm"><tbody>@foreach ($templates as $template)<tr class="border-t border-ink-100"><td class="py-2"><a class="text-civic-700" href="{{ route('backoffice.inspections.templates.edit', $template) }}">{{ $template->name }}</a></td><td>{{ $template->inspection_type?->label() ?? '-' }}</td><td>{{ $template->items->count() }} itens</td></tr>@endforeach</tbody></table>{{ $templates->links() }}</div>
</x-app-layout>
