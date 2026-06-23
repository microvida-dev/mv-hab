<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Registo de adesão</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">Renovações</h1>
                <p class="mt-1 text-sm text-ink-500">Atualize dados base sem recriar todo o registo.</p>
            </div>
            <a href="{{ route('candidate.registration-renewals.create') }}" class="mv-button-primary">Iniciar renovação</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            <section class="mv-surface overflow-hidden">
                <table class="min-w-full divide-y divide-ink-100 text-sm">
                    <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                        <tr><th class="px-5 py-3">Número</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3">Criada</th><th class="px-5 py-3 text-right">Ações</th></tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100 bg-white">
                        @forelse ($renewals as $renewal)
                            <tr>
                                <td class="px-5 py-4 font-semibold text-ink-900">{{ $renewal->renewal_number }}</td>
                                <td class="px-5 py-4">{{ $renewal->status->label() }}</td>
                                <td class="px-5 py-4">{{ $renewal->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-5 py-4 text-right"><a href="{{ route('candidate.registration-renewals.show', $renewal) }}" class="font-semibold text-civic-700">Consultar</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-8 text-center text-sm text-ink-500">Ainda não existem renovações.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
            {{ $renewals->links() }}
        </div>
    </div>
</x-app-layout>
