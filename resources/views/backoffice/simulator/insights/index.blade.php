<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Backoffice</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Insights do simulador</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-4 md:grid-cols-4">
                @foreach ($metrics as $label => $value)
                    <div class="mv-surface p-5">
                        <p class="text-xs font-semibold uppercase text-ink-500">{{ $label }}</p>
                        <p class="mt-2 text-2xl font-semibold text-ink-900">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
            <section class="mv-surface overflow-hidden">
                <table class="min-w-full divide-y divide-ink-100 text-sm">
                    <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                        <tr><th class="px-5 py-3">Data</th><th class="px-5 py-3">Utilizador</th><th class="px-5 py-3">Resultado</th><th class="px-5 py-3 text-right">Ações</th></tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100 bg-white">
                        @foreach ($sessions as $session)
                            <tr>
                                <td class="px-5 py-4">{{ $session->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-5 py-4">{{ $session->user?->email ?? 'Anónimo' }}</td>
                                <td class="px-5 py-4">{{ $session->result_status?->label() ?? 'A validar' }}</td>
                                <td class="px-5 py-4 text-right"><a class="font-semibold text-civic-700" href="{{ route('backoffice.simulator.insights.show', $session) }}">Detalhe</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
            {{ $sessions->links() }}
        </div>
    </div>
</x-app-layout>
