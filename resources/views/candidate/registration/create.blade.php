<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Registo de Adesão</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Iniciar registo</h1>
            <p class="mt-1 text-sm text-ink-500">Pode guardar informação parcial e continuar mais tarde.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <form method="POST" action="{{ route('candidate.registration.store') }}" class="mv-surface p-6">
                @csrf
                @include('candidate.registration.partials.form', [
                    'registration' => null,
                    'submitLabel' => 'Guardar rascunho',
                ])
            </form>
        </div>
    </div>
</x-app-layout>
