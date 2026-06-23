<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Avisos de incumprimento</h1></x-slot>
    <div class="mv-card overflow-x-auto"><table class="min-w-full text-sm"><tbody>@foreach ($notices as $notice)<tr class="border-t border-ink-100"><td class="py-2"><a class="text-civic-700" href="{{ route('candidate.finance.default-notices.show', $notice) }}">{{ $notice->notice_number }}</a></td><td>{{ $notice->subject }}</td><td>{{ $notice->status->label() }}</td></tr>@endforeach</tbody></table>{{ $notices->links() }}</div>
</x-app-layout>
