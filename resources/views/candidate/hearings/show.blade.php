<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-civic-700">Audiência</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $hearing->subject }}</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8"><p class="rounded-md bg-civic-50 p-4 text-sm text-civic-900">Foi-lhe concedida audiência de interessados para se pronunciar sobre os elementos indicados. A sua pronúncia deve ser submetida dentro do prazo definido.</p><div class="rounded-md border border-ink-100 bg-white p-6"><p class="text-sm text-ink-500">{{ $hearing->status->label() }} · {{ $hearing->deadline_at->format('d/m/Y H:i') }}</p><p class="mt-4 whitespace-pre-line text-sm">{{ $hearing->message }}</p></div><a class="inline-block rounded-md bg-civic-700 px-4 py-2 text-sm font-semibold text-white" href="{{ route('candidate.hearings.submit', $hearing) }}">Pronunciar</a></div></div>
</x-app-layout>

