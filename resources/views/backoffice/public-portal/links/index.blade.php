<x-app-layout>
    <x-slot name="header"><div class="flex items-center justify-between gap-4"><div><p class="text-sm font-semibold text-civic-700">Portal público</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Ligações institucionais</h1></div><a href="{{ route('backoffice.public-portal.links.create') }}" class="mv-button-primary">Nova ligação</a></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <x-flash-message />
        <div class="overflow-hidden rounded-md border border-ink-100 bg-white">
            <table class="min-w-full divide-y divide-ink-100 text-sm">
                <thead class="bg-ink-50 text-left text-xs uppercase tracking-wide text-ink-500"><tr><th class="px-4 py-3">Ligação</th><th class="px-4 py-3">Categoria</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3 text-right">Ações</th></tr></thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($links as $link)
                        <tr><td class="px-4 py-3 font-semibold text-ink-900">{{ $link->label }}</td><td class="px-4 py-3">{{ $link->category }}</td><td class="px-4 py-3">{{ $link->is_active ? 'Ativa' : 'Inativa' }}</td><td class="px-4 py-3 text-right"><a href="{{ route('backoffice.public-portal.links.edit', $link) }}" class="font-semibold text-civic-700">Editar</a></td></tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-ink-500">Sem ligações registadas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $links->links() }}</div>
    </div></div>
</x-app-layout>
