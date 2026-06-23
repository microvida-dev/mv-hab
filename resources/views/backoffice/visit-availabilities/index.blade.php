<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Backoffice</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">Disponibilidade de visitas</h1>
            </div>
            <a href="{{ route('backoffice.visit-availabilities.create') }}" class="mv-button-primary">Nova disponibilidade</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            <section class="mv-surface overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-ink-100 text-sm">
                        <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                            <tr>
                                <th class="px-5 py-3">Título</th>
                                <th class="px-5 py-3">Período</th>
                                <th class="px-5 py-3">Contexto</th>
                                <th class="px-5 py-3">Técnico</th>
                                <th class="px-5 py-3 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @forelse ($availabilities as $availability)
                                <tr>
                                    <td class="px-5 py-4 font-semibold text-ink-900">{{ $availability->title }}</td>
                                    <td class="px-5 py-4 text-ink-700">{{ $availability->starts_at?->format('d/m/Y H:i') }} a {{ $availability->ends_at?->format('d/m/Y H:i') }}</td>
                                    <td class="px-5 py-4 text-ink-700">{{ $availability->housingUnit?->title ?? $availability->contest?->title ?? 'Geral' }}</td>
                                    <td class="px-5 py-4 text-ink-600">{{ $availability->staff?->name ?? '—' }}</td>
                                    <td class="px-5 py-4 text-right"><a href="{{ route('backoffice.visit-availabilities.show', $availability) }}" class="font-semibold text-civic-700">Consultar</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-8 text-center text-ink-500">Sem disponibilidades registadas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
            {{ $availabilities->links() }}
        </div>
    </div>
</x-app-layout>
