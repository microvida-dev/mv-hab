<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold text-ink-900">Atualização documental anual</h1></x-slot>
    <div class="mv-card overflow-x-auto"><table class="min-w-full text-sm"><tbody>@foreach ($requests as $request)<tr class="border-t border-ink-100"><td class="py-2"><a class="text-civic-700" href="{{ route('candidate.finance.annual-document-updates.show', $request) }}">{{ $request->request_number }}</a></td><td>{{ $request->reference_year }}</td><td>{{ $request->status->label() }}</td></tr>@endforeach</tbody></table>{{ $requests->links() }}</div>
</x-app-layout>
