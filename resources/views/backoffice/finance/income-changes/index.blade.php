<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Alterações de rendimentos</h1></x-slot>
    <div class="mv-card overflow-x-auto"><table class="min-w-full text-sm"><tbody>@foreach ($declarations as $declaration)<tr class="border-t border-ink-100"><td class="py-2"><a class="text-mvhab-primary" href="{{ route('backoffice.finance.income-changes.show', $declaration) }}">Declaração #{{ $declaration->id }}</a></td><td>{{ $declaration->tenant?->name }}</td><td>{{ $declaration->status->label() }}</td></tr>@endforeach</tbody></table>{{ $declarations->links() }}</div>
</x-app-layout>
