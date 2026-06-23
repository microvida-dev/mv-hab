<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Agregado familiar</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Adicionar membro</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('candidate.household-members.store') }}" class="mv-surface p-6">
                @csrf
                @include('candidate.household-members.partials.form')
                <div class="mt-8 flex flex-wrap justify-end gap-3 border-t border-ink-100 pt-6">
                    <a href="{{ route('candidate.household-members.index') }}" class="mv-button-secondary">Voltar</a>
                    <button type="submit" class="mv-button-primary">Guardar membro</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
