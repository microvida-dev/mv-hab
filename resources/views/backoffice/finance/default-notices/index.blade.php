<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Avisos de incumprimento</h1></x-slot>
    <a class="mv-button-primary" href="{{ route('backoffice.finance.default-notices.create') }}">Criar aviso</a>
    <div class="mv-card mt-4 overflow-x-auto"><table class="min-w-full text-sm"><tbody>@foreach ($notices as $notice)<tr class="border-t border-ink-100"><td class="py-2"><a class="text-mvhab-primary" href="{{ route('backoffice.finance.default-notices.show', $notice) }}">{{ $notice->notice_number }}</a></td><td>{{ $notice->tenant?->name }}</td><td>{{ $notice->status->label() }}</td></tr>@endforeach</tbody></table>{{ $notices->links() }}</div>
</x-app-layout>
