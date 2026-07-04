<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Agregado familiar"
            title="Adicionar membro"
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('candidate.household-members.store') }}" class="space-y-6">
                @csrf
                @include('candidate.household-members.partials.form')
                <div class="flex flex-wrap justify-end gap-3">
                    <a href="{{ route('candidate.household-members.index') }}" class="mv-button-secondary">Voltar</a>
                    <button type="submit" class="mv-button-primary">Guardar membro</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
