<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Comprovativo"
            title="Comprovativo de Submissão de Candidatura"
            :description="$application->application_number"
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <x-mv.alert tone="success">
                A sua candidatura foi submetida com sucesso. Guarde este comprovativo para referência futura. Poderá acompanhar o estado da candidatura na sua área pessoal.
            </x-mv.alert>

            @include('candidate.applications.partials.receipt-content')

            <div class="flex flex-wrap justify-end gap-3">
                <a href="{{ route('candidate.applications.index') }}" class="mv-button-secondary">As minhas candidaturas</a>
                <a href="{{ route('candidate.applications.print', $application) }}" class="mv-button-primary">Versão para imprimir</a>
            </div>
        </div>
    </div>
</x-app-layout>
