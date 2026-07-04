<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Área do candidato"
            title="Preferências de habitação"
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <x-mv.section title="Candidaturas com preferências">
                <div class="divide-y divide-ink-100">
                    @forelse($applications as $application)
                        <div class="flex items-center justify-between gap-4 py-4">
                            <div>
                                <p class="font-semibold">{{ $application->contest?->title }}</p>
                                <x-mv.badge class="mt-2">{{ $application->housingPreferences->count() }} preferência(s) registada(s)</x-mv.badge>
                            </div>
                            <a class="mv-button-secondary" href="{{ route('candidate.housing-preferences.edit', $application) }}">Gerir</a>
                        </div>
                    @empty
                        <x-mv.alert>
                            Não existem candidaturas prontas para preferências de atribuição.
                        </x-mv.alert>
                    @endforelse
                </div>
            </x-mv.section>
        </div>
    </div>
</x-app-layout>
