<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-mvhab-primary">Comprovativo</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Comprovativo de Submissão de Candidatura</h1>
            <p class="mt-1 text-sm text-ink-500">{{ $application->application_number }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <section class="rounded-2xl border border-mvhab-support/30 bg-mvhab-surface p-5 text-sm leading-6 text-mvhab-primary">
                A sua candidatura foi submetida com sucesso. Guarde este comprovativo para referência futura. Poderá acompanhar o estado da candidatura na sua área pessoal.
            </section>

            @include('candidate.applications.partials.receipt-content')

            <div class="flex flex-wrap justify-end gap-3">
                <a href="{{ route('candidate.applications.index') }}" class="mv-button-secondary">As minhas candidaturas</a>
                <a href="{{ route('candidate.applications.print', $application) }}" class="mv-button-primary">Versão para imprimir</a>
            </div>
        </div>
    </div>
</x-app-layout>
