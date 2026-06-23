<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-civic-700">Decisão</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $complaintDecision->decision_number }}</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8"><div class="rounded-md border border-ink-100 bg-white p-6"><p class="text-sm text-ink-500">{{ $complaintDecision->status->label() }} · {{ $complaintDecision->decision_result->label() }}</p><h2 class="mt-4 font-semibold">Resumo</h2><p class="mt-2 whitespace-pre-line text-sm">{{ $complaintDecision->summary }}</p><h2 class="mt-4 font-semibold">Fundamentos</h2><p class="mt-2 whitespace-pre-line text-sm">{{ $complaintDecision->grounds }}</p></div><form method="POST" action="{{ route('backoffice.complaint-decisions.approve', $complaintDecision) }}">@csrf<x-primary-button>Aprovar decisão</x-primary-button></form></div></div>
</x-app-layout>

