<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Interações</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Histórico de interações</h1>
            <p class="mt-1 text-sm text-ink-500">Registos visíveis do acompanhamento municipal.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if ($inconsistencies->isNotEmpty())
                <section class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Inconsistências por rever</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($inconsistencies as $item)
                            <p class="text-sm text-ink-700">{{ $item->message }} <span class="font-semibold text-ink-500">({{ $item->severity->label() }})</span></p>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="mv-surface overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-ink-100 text-sm">
                        <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                            <tr>
                                <th class="px-5 py-3">Data</th>
                                <th class="px-5 py-3">Tipo</th>
                                <th class="px-5 py-3">Título</th>
                                <th class="px-5 py-3">Contexto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @forelse ($interactions as $interaction)
                                <tr>
                                    <td class="px-5 py-4 text-ink-600">{{ $interaction->created_at?->format('d/m/Y H:i') }}</td>
                                    <td class="px-5 py-4 text-ink-700">{{ $interaction->type->label() }}</td>
                                    <td class="px-5 py-4 font-semibold text-ink-900">{{ $interaction->title }}</td>
                                    <td class="px-5 py-4 text-ink-600">{{ $interaction->contest?->title ?? $interaction->application?->application_number ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-5 py-8 text-center text-ink-500">Ainda não existem interações visíveis.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
            {{ $interactions->links() }}
        </div>
    </div>
</x-app-layout>
