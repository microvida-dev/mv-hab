<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-mvhab-primary">Procedimento</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">Minutas de procedimento</h1>
                <p class="mt-1 text-sm text-ink-500">Modelos para relatórios, listas, atas, confirmações e comunicações internas.</p>
            </div>
            <a href="{{ route('backoffice.procedure-templates.create') }}" class="mv-button-primary">Nova minuta</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-2xl border border-mvhab-support/40 bg-mvhab-surface px-4 py-3 text-sm font-semibold text-mvhab-primary">{{ session('success') }}</div>
            @endif
            <div class="mv-surface overflow-hidden">
                <table class="min-w-full divide-y divide-ink-100 text-sm">
                    <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500"><tr><th class="px-4 py-3">Número</th><th class="px-4 py-3">Nome</th><th class="px-4 py-3">Tipo</th><th class="px-4 py-3">Estado</th><th></th></tr></thead>
                    <tbody class="divide-y divide-ink-100">
                        @forelse ($templates as $template)
                            <tr>
                                <td class="px-4 py-3 font-mono text-xs">{{ $template->template_number }}</td>
                                <td class="px-4 py-3 font-semibold text-ink-900">{{ $template->name }}</td>
                                <td class="px-4 py-3">{{ $template->type->label() }}</td>
                                <td class="px-4 py-3">{{ $template->status->label() }}</td>
                                <td class="px-4 py-3 text-right"><a class="font-semibold text-mvhab-primary" href="{{ route('backoffice.procedure-templates.show', $template) }}">Abrir</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-ink-500">Sem minutas de procedimento.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $templates->links() }}
        </div>
    </div>
</x-app-layout>
