<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Backoffice operacional</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">Alertas internos</h1>
                <p class="mt-1 text-sm text-ink-500">Sinalização de prazos, bloqueios documentais e inconsistências processuais.</p>
            </div>
            <form method="POST" action="{{ route('backoffice.internal-alerts.detect') }}">
                @csrf
                <button class="mv-button-primary">Detetar alertas</button>
            </form>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-md border border-civic-200 bg-civic-50 px-4 py-3 text-sm font-semibold text-civic-800">{{ session('success') }}</div>
            @endif
            <div class="mv-surface overflow-hidden">
                <table class="min-w-full divide-y divide-ink-100 text-sm">
                    <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500"><tr><th class="px-4 py-3">Alerta</th><th class="px-4 py-3">Tipo</th><th class="px-4 py-3">Severidade</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3">Prazo</th><th></th></tr></thead>
                    <tbody class="divide-y divide-ink-100">
                        @forelse ($alerts as $alert)
                            <tr>
                                <td class="px-4 py-3"><span class="font-semibold text-ink-900">{{ $alert->title }}</span><p class="mt-1 text-xs text-ink-500">{{ $alert->alert_number }}</p></td>
                                <td class="px-4 py-3">{{ $alert->type->label() }}</td>
                                <td class="px-4 py-3">{{ $alert->severity->label() }}</td>
                                <td class="px-4 py-3">{{ $alert->status->label() }}</td>
                                <td class="px-4 py-3">{{ $alert->due_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="px-4 py-3 text-right"><a class="font-semibold text-civic-700" href="{{ route('backoffice.internal-alerts.show', $alert) }}">Abrir</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-10 text-center text-ink-500">Sem alertas internos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $alerts->links() }}
        </div>
    </div>
</x-app-layout>
