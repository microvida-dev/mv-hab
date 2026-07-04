<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Visitas abertas"
            :title="$availability->title"
            description="Janela de visita aberta para candidatos. Gere horários para disponibilizar marcações na área do candidato."
        >
            <x-slot name="actions">
            <a href="{{ route('backoffice.visit-availabilities.edit', $availability) }}" class="mv-button-secondary">Editar</a>
            </x-slot>
        </x-mv.page-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            <section class="grid gap-4 md:grid-cols-3">
                <x-mv.stat-card label="Início" :value="$availability->starts_at?->format('d/m/Y H:i') ?? '—'" />
                <x-mv.stat-card label="Fim" :value="$availability->ends_at?->format('d/m/Y H:i') ?? '—'" />
                <x-mv.stat-card label="Horários" :value="$availability->slots->count()" />
            </section>

            <form method="POST" action="{{ route('backoffice.visit-availabilities.slots.generate', $availability) }}">
                @csrf
                <x-mv.section title="Gerar horários" description="Defina o local e o ponto de encontro a aplicar aos horários desta janela.">
                    <div class="grid gap-4 md:grid-cols-[1fr_1fr_auto]">
                        <input name="location" placeholder="Local" class="mv-input text-sm">
                        <input name="meeting_point" placeholder="Ponto de encontro" class="mv-input text-sm">
                        <button type="submit" class="mv-button-primary">Gerar horários</button>
                    </div>
                </x-mv.section>
            </form>

            <x-mv.section title="Horários publicados" padding="p-0" class="overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-ink-100 text-sm">
                        <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                            <tr><th class="px-5 py-3">Horário</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3">Reservas</th><th class="px-5 py-3">Local</th></tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @forelse ($availability->slots as $slot)
                                <tr>
                                    <td class="px-5 py-4 text-ink-900">{{ $slot->starts_at?->format('d/m/Y H:i') }} a {{ $slot->ends_at?->format('H:i') }}</td>
                                    <td class="px-5 py-4"><x-mv.badge>{{ $slot->status->label() }}</x-mv.badge></td>
                                    <td class="px-5 py-4 text-ink-700">{{ $slot->booked_count }}/{{ $slot->capacity }}</td>
                                    <td class="px-5 py-4 text-ink-600">{{ $slot->location ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-5 py-8 text-center text-ink-500">Ainda não existem horários gerados para esta visita aberta.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-mv.section>
        </div>
    </div>
</x-app-layout>
