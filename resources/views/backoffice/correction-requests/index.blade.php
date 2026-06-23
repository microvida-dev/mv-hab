<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Aperfeiçoamento</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $administrativeProcess->process_number }}</h1>
            </div>
            <a href="{{ route('backoffice.correction-requests.create', $administrativeProcess) }}" class="mv-button-primary">Novo pedido</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <section class="mv-surface overflow-hidden">
                <table class="min-w-full divide-y divide-ink-100 text-sm">
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($requests as $request)
                            <tr>
                                <td class="px-5 py-4 font-semibold text-ink-900">{{ $request->request_number }}</td>
                                <td class="px-5 py-4 text-ink-700">{{ $request->subject }}</td>
                                <td class="px-5 py-4 text-ink-600">{{ $request->status->label() }}</td>
                                <td class="px-5 py-4 text-right"><a class="font-semibold text-civic-700" href="{{ route('backoffice.correction-requests.show', $request) }}">Consultar</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
            {{ $requests->links() }}
        </div>
    </div>
</x-app-layout>
