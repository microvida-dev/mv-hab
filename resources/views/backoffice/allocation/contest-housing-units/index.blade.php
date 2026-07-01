<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-mvhab-primary">Atribuição</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">Habitações por concurso</h1>
            </div>
            <a href="{{ route('backoffice.allocation.contest-housing-units.create') }}" class="mv-button-primary">Associar habitação</a>
        </div>
    </x-slot>
    <div class="py-8"><div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
        <x-flash-message />
        <div class="overflow-hidden rounded-2xl border border-ink-100 bg-white">
            <table class="min-w-full divide-y divide-ink-100 text-sm">
                <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500"><tr><th class="px-4 py-3">Concurso</th><th class="px-4 py-3">Habitação</th><th class="px-4 py-3">Tipologia</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3"></th></tr></thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse($units as $unit)
                        <tr><td class="px-4 py-3">{{ $unit->contest?->title ?? $unit->program?->name ?? 'Sem âmbito' }}</td><td class="px-4 py-3 font-semibold">{{ $unit->housingUnit?->code }}</td><td class="px-4 py-3">{{ $unit->typology ?? $unit->housingUnit?->typology }}</td><td class="px-4 py-3">{{ $unit->status->label() }}</td><td class="px-4 py-3 text-right"><a class="font-semibold text-mvhab-primary" href="{{ route('backoffice.allocation.contest-housing-units.show', $unit) }}">Abrir</a></td></tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-ink-500">Sem habitações associadas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $units->links() }}
    </div></div>
</x-app-layout>
