<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-civic-700">Reclamações</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">As minhas reclamações</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8"><div class="mb-4"><a href="{{ route('candidate.complaints.create') }}" class="rounded-md bg-civic-700 px-4 py-2 text-sm font-semibold text-white">Nova reclamação</a></div><div class="rounded-md border border-ink-100 bg-white">@forelse($complaints as $complaint)<a href="{{ route('candidate.complaints.show', $complaint) }}" class="block border-b border-ink-100 p-4"><span class="font-semibold">{{ $complaint->complaint_number }}</span><span class="ml-2 text-sm text-ink-500">{{ $complaint->subject }} · {{ $complaint->status->label() }}</span></a>@empty<p class="p-6 text-sm text-ink-500">Sem reclamações.</p>@endforelse</div><div class="mt-4">{{ $complaints->links() }}</div></div></div>
</x-app-layout>

