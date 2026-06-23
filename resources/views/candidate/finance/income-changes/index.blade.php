<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Alterações de rendimento</h1></x-slot>
    <a class="mv-button-primary" href="{{ route('candidate.finance.income-changes.create') }}">Declarar alteração</a>
    <div class="mv-card mt-4 overflow-x-auto"><table class="min-w-full text-sm"><tbody>@foreach ($declarations as $declaration)<tr class="border-t border-ink-100"><td class="py-2"><a class="text-civic-700" href="{{ route('candidate.finance.income-changes.show', $declaration) }}">Declaração #{{ $declaration->id }}</a></td><td>{{ $declaration->status->label() }}</td></tr>@endforeach</tbody></table>{{ $declarations->links() }}</div>
</x-app-layout>
