<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-civic-700">Área do candidato</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Preferências de habitação</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8">
        <x-flash-message />
        <div class="rounded-md border border-ink-100 bg-white">
            @forelse($applications as $application)
                <div class="flex items-center justify-between gap-4 border-b border-ink-100 p-4">
                    <div><p class="font-semibold">{{ $application->contest?->title }}</p><p class="text-sm text-ink-500">{{ $application->housingPreferences->count() }} preferência(s) registada(s)</p></div>
                    <a class="rounded-md border border-ink-200 px-3 py-2 text-sm font-semibold" href="{{ route('candidate.housing-preferences.edit', $application) }}">Gerir</a>
                </div>
            @empty
                <p class="p-6 text-sm text-ink-500">Não existem candidaturas prontas para preferências de atribuição.</p>
            @endforelse
        </div>
    </div></div>
</x-app-layout>
