<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-semibold text-ink-900">Reutilização de dados</h1></x-slot>
    <div class="py-8"><div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8"><section class="mv-surface overflow-hidden"><table class="min-w-full divide-y divide-ink-100 text-sm"><tbody class="divide-y divide-ink-100">@forelse($reuses as $reuse)<tr><td class="px-5 py-4 font-semibold">{{ $reuse->user?->name }}</td><td class="px-5 py-4">{{ $reuse->status->label() }}</td><td class="px-5 py-4">{{ implode(', ', $reuse->sections ?? []) }}</td></tr>@empty<tr><td class="px-5 py-8 text-center text-ink-500">Sem reutilizações.</td></tr>@endforelse</tbody></table></section>{{ $reuses->links() }}</div></div>
</x-app-layout>
