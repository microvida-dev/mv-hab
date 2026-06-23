<x-app-layout>
    <x-slot name="header">
        <div class="flex items-end justify-between gap-4">
            <div><p class="text-sm font-semibold text-civic-700">Comunicações</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Templates</h1></div>
            @can('create', \App\Models\NotificationTemplate::class)
                <a href="{{ route('backoffice.communications.templates.create') }}" class="mv-button-primary">Novo template</a>
            @endcan
        </div>
    </x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-md border border-ink-100 bg-white">
            <table class="min-w-full divide-y divide-ink-100 text-sm">
                <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500"><tr><th class="px-4 py-3">Código</th><th class="px-4 py-3">Nome</th><th class="px-4 py-3">Canal</th><th class="px-4 py-3">Versão ativa</th><th class="px-4 py-3">Estado</th><th></th></tr></thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($templates as $template)
                        <tr><td class="px-4 py-3 font-mono text-xs">{{ $template->code }}</td><td class="px-4 py-3 font-semibold">{{ $template->name }}</td><td class="px-4 py-3">{{ $template->channel->label() }}</td><td class="px-4 py-3">{{ $template->activeVersion?->version_number ? 'v'.$template->activeVersion->version_number : 'Sem versão ativa' }}</td><td class="px-4 py-3">{{ $template->status->label() }}</td><td class="px-4 py-3 text-right"><a href="{{ route('backoffice.communications.templates.show', $template) }}" class="font-semibold text-civic-700">Abrir</a></td></tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-ink-500">Sem templates configurados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $templates->links() }}</div>
    </div></div>
</x-app-layout>
