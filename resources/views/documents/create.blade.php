<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">Novo documento</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
            <x-flash-message />

            <div class="mv-surface p-6">
                <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    @include('documents.partials.form')

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('documents.index') }}" class="mv-button-secondary">
                            Cancelar
                        </a>
                        <x-primary-button>Guardar</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
