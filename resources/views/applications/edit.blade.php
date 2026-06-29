<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">Editar candidatura</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
            <x-flash-message />

            <div class="mv-surface p-6">
                <form method="POST" action="{{ route('applications.update', $application) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    @include('applications.partials.form')

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('applications.show', $application) }}" class="mv-button-secondary">
                            Cancelar
                        </a>
                        <x-primary-button>Atualizar</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
