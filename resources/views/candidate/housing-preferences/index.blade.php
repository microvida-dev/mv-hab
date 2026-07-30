<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Área do candidato"
            title="Fogos"
            description="Ordene todos os fogos compatíveis de cada candidatura em rascunho."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <x-mv.section title="Candidaturas em preparação">
                <div class="divide-y divide-ink-100">
                    @forelse($applications as $application)
                        <div class="flex items-center justify-between gap-4 py-4">
                            <div>
                                <p class="font-semibold">{{ $application->contest?->title }}</p>
                                <x-mv.badge class="mt-2">{{ $application->housingPreferences->count() }} fogo(s) ordenado(s)</x-mv.badge>
                            </div>
                            <a class="mv-button-secondary" href="{{ route('candidate.housing-preferences.edit', $application) }}">
                                Ordenar fogos
                            </a>
                        </div>
                    @empty
                        <x-mv.alert>
                            Não existem candidaturas em rascunho para ordenar fogos.
                        </x-mv.alert>
                    @endforelse
                </div>

                @if ($applications->hasPages())
                    <div class="mt-5">
                        {{ $applications->links() }}
                    </div>
                @endif
            </x-mv.section>
        </div>
    </div>
</x-app-layout>
