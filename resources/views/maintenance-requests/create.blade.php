<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-ink-900">Novo pedido de manutenção</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
            <x-flash-message />

            <div class="mv-surface p-6">
                <form method="POST" action="{{ route('maintenance-requests.store') }}" class="space-y-6">
                    @csrf

                    @include('maintenance-requests.partials.form')

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('maintenance-requests.index') }}" class="mv-button-secondary">
                            Cancelar
                        </a>
                        <x-primary-button>Guardar</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
