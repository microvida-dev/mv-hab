<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Área pessoal</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Pedidos de aperfeiçoamento</h1>
        </div>
    </x-slot>
    <div class="py-8">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <section class="mv-surface p-6">
                <p class="text-sm leading-6 text-ink-600">Os serviços municipais solicitaram informação adicional ou correção de elementos da sua candidatura. Responda dentro do prazo indicado para que a análise possa prosseguir.</p>
            </section>
            <section class="mv-surface mt-6 overflow-hidden">
                <table class="min-w-full divide-y divide-ink-100 text-sm">
                    <tbody class="divide-y divide-ink-100">
                        @forelse ($requests as $request)
                            <tr>
                                <td class="px-5 py-4 font-semibold text-ink-900">{{ $request->request_number }}</td>
                                <td class="px-5 py-4 text-ink-700">{{ $request->application->application_number }}</td>
                                <td class="px-5 py-4 text-ink-600">{{ $request->status->label() }}</td>
                                <td class="px-5 py-4 text-ink-500">{{ $request->response_deadline_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="px-5 py-4 text-right"><a href="{{ route('candidate.correction-requests.show', $request) }}" class="font-semibold text-civic-700">Consultar</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-8 text-center text-ink-500">Não existem pedidos visíveis.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
            {{ $requests->links() }}
        </div>
    </div>
</x-app-layout>
