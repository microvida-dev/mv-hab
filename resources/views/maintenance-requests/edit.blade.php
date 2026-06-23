<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">Editar pedido de manutenção</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
            <x-flash-message />

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="POST" action="{{ route('maintenance-requests.update', $maintenanceRequest) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    @include('maintenance-requests.partials.form')

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('maintenance-requests.show', $maintenanceRequest) }}" class="inline-flex items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            Cancelar
                        </a>
                        <x-primary-button>Atualizar</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
