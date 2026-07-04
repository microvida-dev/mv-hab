<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Rendimentos"
            title="Editar rendimento"
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('candidate.income-records.update', $incomeRecord) }}" class="space-y-6">
                @csrf
                @method('PUT')
                @include('candidate.income-records.partials.form')
                <div class="flex flex-wrap justify-end gap-3">
                    <a href="{{ route('candidate.income-records.index') }}" class="mv-button-secondary">Voltar</a>
                    <button type="submit" class="mv-button-primary">Guardar alterações</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
