<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">Pedidos de documentos adicionais</h1></x-slot>
    <div class="py-8"><div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <section class="mv-surface overflow-hidden">
            <table class="min-w-full divide-y divide-ink-100 text-sm">
                <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500"><tr><th class="px-5 py-3">Pedido</th><th class="px-5 py-3">Candidatura</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3">Prazo</th></tr></thead>
                <tbody class="divide-y divide-ink-100">@forelse($requests as $request)<tr><td class="px-5 py-4 font-semibold">{{ $request->title }}</td><td class="px-5 py-4">{{ $request->application?->application_number }}</td><td class="px-5 py-4">{{ $request->status->label() }}</td><td class="px-5 py-4">{{ $request->due_at?->format('d/m/Y H:i') ?? '—' }}</td></tr>@empty<tr><td colspan="4" class="px-5 py-8 text-center text-ink-500">Sem pedidos.</td></tr>@endforelse</tbody>
            </table>
        </section>
        {{ $requests->links() }}
    </div></div>
</x-app-layout>
