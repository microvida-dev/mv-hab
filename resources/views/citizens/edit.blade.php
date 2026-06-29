<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-ink-900">Editar munícipe</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
            <x-flash-message />

            <div class="mv-surface p-6">
                <form method="POST" action="{{ route('citizens.update', $citizen) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    @include('citizens.partials.form')

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('citizens.show', $citizen) }}" class="mv-button-secondary">
                            Cancelar
                        </a>
                        <x-primary-button>Atualizar</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
