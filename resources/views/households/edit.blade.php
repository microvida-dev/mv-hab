<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-ink-900">Editar agregado familiar</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
            <x-flash-message />

            <div class="mv-surface p-6">
                <form method="POST" action="{{ route('households.update', $household) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    @include('households.partials.form')

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('households.show', $household) }}" class="mv-button-secondary">
                            Cancelar
                        </a>
                        <x-primary-button>Atualizar</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
