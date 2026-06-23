<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Backoffice</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Inconsistências simulação-candidatura</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            <section class="mv-surface overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-ink-100 text-sm">
                        <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                            <tr><th class="px-5 py-3">Candidato</th><th class="px-5 py-3">Candidatura</th><th class="px-5 py-3">Tipo</th><th class="px-5 py-3">Severidade</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3 text-right">Ação</th></tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @forelse ($inconsistencies as $item)
                                <tr>
                                    <td class="px-5 py-4 text-ink-700">{{ $item->user?->name }}</td>
                                    <td class="px-5 py-4 text-ink-700">{{ $item->application?->application_number ?? 'Rascunho' }}</td>
                                    <td class="px-5 py-4 font-semibold text-ink-900">{{ $item->type->label() }}</td>
                                    <td class="px-5 py-4 text-ink-700">{{ $item->severity->label() }}</td>
                                    <td class="px-5 py-4 text-ink-700">{{ $item->is_resolved ? 'Resolvida' : 'Aberta' }}</td>
                                    <td class="px-5 py-4 text-right">
                                        @unless ($item->is_resolved)
                                            <form method="POST" action="{{ route('backoffice.application-inconsistencies.resolve', $item) }}" class="flex justify-end gap-2">
                                                @csrf
                                                <input name="resolution_note" class="w-48 rounded-md border-ink-300 text-sm" placeholder="Nota">
                                                <button class="font-semibold text-civic-700">Resolver</button>
                                            </form>
                                        @endunless
                                    </td>
                                </tr>
                                <tr><td colspan="6" class="px-5 pb-4 text-sm text-ink-600">{{ $item->message }}</td></tr>
                            @empty
                                <tr><td colspan="6" class="px-5 py-8 text-center text-ink-500">Sem inconsistências registadas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
            {{ $inconsistencies->links() }}
        </div>
    </div>
</x-app-layout>
