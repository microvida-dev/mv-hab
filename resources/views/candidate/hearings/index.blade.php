<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-civic-700">Audiências</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Audiência de interessados</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8"><div class="rounded-md border border-ink-100 bg-white">@forelse($hearings as $hearing)<a href="{{ route('candidate.hearings.show', $hearing) }}" class="block border-b border-ink-100 p-4"><span class="font-semibold">{{ $hearing->subject }}</span><span class="ml-2 text-sm text-ink-500">{{ $hearing->status->label() }} · prazo {{ $hearing->deadline_at->format('d/m/Y H:i') }}</span></a>@empty<p class="p-6 text-sm text-ink-500">Sem audiências disponíveis.</p>@endforelse</div><div class="mt-4">{{ $hearings->links() }}</div></div></div>
</x-app-layout>

