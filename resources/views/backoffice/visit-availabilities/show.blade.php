<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Disponibilidade</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">{{ $availability->title }}</h1>
            </div>
            <a href="{{ route('backoffice.visit-availabilities.edit', $availability) }}" class="mv-button-secondary">Editar</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            <section class="grid gap-4 md:grid-cols-3">
                <div class="mv-surface p-5"><p class="text-xs font-semibold uppercase text-ink-500">Início</p><p class="mt-2 font-semibold text-ink-900">{{ $availability->starts_at?->format('d/m/Y H:i') }}</p></div>
                <div class="mv-surface p-5"><p class="text-xs font-semibold uppercase text-ink-500">Fim</p><p class="mt-2 font-semibold text-ink-900">{{ $availability->ends_at?->format('d/m/Y H:i') }}</p></div>
                <div class="mv-surface p-5"><p class="text-xs font-semibold uppercase text-ink-500">Slots</p><p class="mt-2 font-semibold text-ink-900">{{ $availability->slots->count() }}</p></div>
            </section>

            <form method="POST" action="{{ route('backoffice.visit-availabilities.slots.generate', $availability) }}" class="mv-surface grid gap-4 p-6 md:grid-cols-[1fr_1fr_auto]">
                @csrf
                <input name="location" placeholder="Local" class="rounded-md border-ink-300 text-sm">
                <input name="meeting_point" placeholder="Ponto de encontro" class="rounded-md border-ink-300 text-sm">
                <button type="submit" class="mv-button-primary">Gerar slots</button>
            </form>

            <section class="mv-surface overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-ink-100 text-sm">
                        <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                            <tr><th class="px-5 py-3">Horário</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3">Reservas</th><th class="px-5 py-3">Local</th></tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @forelse ($availability->slots as $slot)
                                <tr>
                                    <td class="px-5 py-4 text-ink-900">{{ $slot->starts_at?->format('d/m/Y H:i') }} a {{ $slot->ends_at?->format('H:i') }}</td>
                                    <td class="px-5 py-4 text-ink-700">{{ $slot->status->label() }}</td>
                                    <td class="px-5 py-4 text-ink-700">{{ $slot->booked_count }}/{{ $slot->capacity }}</td>
                                    <td class="px-5 py-4 text-ink-600">{{ $slot->location ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-5 py-8 text-center text-ink-500">Ainda não existem slots gerados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
