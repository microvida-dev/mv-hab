<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Confirmação de processo"
            :title="$processConfirmation->process_number"
            :description="$processConfirmation->confirmation_number"
        >
            <x-slot name="actions">
                <form method="POST" action="{{ route('backoffice.process-confirmations.send', $processConfirmation) }}">
                    @csrf
                    <button type="submit" class="mv-button-primary">Marcar como enviada</button>
                </form>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <x-mv.alert tone="success">{{ session('success') }}</x-mv.alert>
            @endif

            <x-mv.section :title="$processConfirmation->title">
                <p class="mt-4 text-sm leading-6 text-ink-700">{{ $processConfirmation->message }}</p>
                @if ($processConfirmation->application)
                    <a class="mt-4 inline-block font-semibold text-civic-700" href="{{ route('backoffice.applications.show', $processConfirmation->application) }}">Abrir candidatura</a>
                @endif
            </x-mv.section>

            <x-mv.section title="Dados preservados">
                <pre class="mt-4 overflow-auto rounded-md bg-ink-50 p-4 text-xs">{{ json_encode($processConfirmation->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </x-mv.section>
        </div>
    </div>
</x-app-layout>
