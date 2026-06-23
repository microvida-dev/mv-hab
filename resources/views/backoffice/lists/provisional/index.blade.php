<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-civic-700">Listas</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Listas provisórias</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-4 flex justify-end"><a href="{{ route('backoffice.lists.provisional.create') }}" class="rounded-md bg-civic-700 px-4 py-2 text-sm font-semibold text-white">Gerar lista</a></div>
        <div class="overflow-hidden rounded-md border border-ink-100 bg-white"><table class="min-w-full divide-y divide-ink-100 text-sm"><thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500"><tr><th class="px-4 py-3">Número</th><th class="px-4 py-3">Concurso</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3">Entradas</th><th class="px-4 py-3"></th></tr></thead><tbody class="divide-y divide-ink-100">@forelse ($lists as $list)<tr><td class="px-4 py-3 font-semibold">{{ $list->list_number }}</td><td class="px-4 py-3">{{ $list->contest?->title ?? $list->program?->name }}</td><td class="px-4 py-3">{{ $list->status->label() }}</td><td class="px-4 py-3">{{ $list->entries_count }}</td><td class="px-4 py-3 text-right"><a class="font-semibold text-civic-700" href="{{ route('backoffice.lists.provisional.show', $list) }}">Abrir</a></td></tr>@empty<tr><td colspan="5" class="px-4 py-8 text-center text-ink-500">Sem listas provisórias.</td></tr>@endforelse</tbody></table></div><div class="mt-4">{{ $lists->links() }}</div>
    </div></div>
</x-app-layout>

