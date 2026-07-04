<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Visitas abertas"
            title="Horários de visita"
            description="Consulte, bloqueie ou desbloqueie horários publicados a partir das visitas abertas definidas pelo município."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />
            <x-mv.section title="Horários disponíveis" padding="p-0" class="overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-ink-100 text-sm">
                        <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                            <tr><th class="px-5 py-3">Horário</th><th class="px-5 py-3">Contexto</th><th class="px-5 py-3">Estado</th><th class="px-5 py-3">Reservas</th><th class="px-5 py-3 text-right">Ações</th></tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @forelse ($slots as $slot)
                                <tr>
                                    <td class="px-5 py-4 text-ink-900">{{ $slot->starts_at?->format('d/m/Y H:i') }} a {{ $slot->ends_at?->format('H:i') }}</td>
                                    <td class="px-5 py-4 text-ink-700">{{ $slot->housingUnit?->title ?? $slot->contest?->title ?? $slot->availability?->title ?? 'Geral' }}</td>
                                    <td class="px-5 py-4"><x-mv.badge>{{ $slot->status->label() }}</x-mv.badge></td>
                                    <td class="px-5 py-4 text-ink-700">{{ $slot->booked_count }}/{{ $slot->capacity }}</td>
                                    <td class="px-5 py-4 text-right">
                                        @if ($slot->status->value === 'blocked')
                                            <form method="POST" action="{{ route('backoffice.visit-slots.unblock', $slot) }}">@csrf<button class="font-semibold text-civic-700">Desbloquear</button></form>
                                        @else
                                            <form method="POST" action="{{ route('backoffice.visit-slots.block', $slot) }}">@csrf<button class="font-semibold text-civic-700">Bloquear</button></form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-8 text-center text-ink-500">Sem horários de visita registados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-mv.section>
            {{ $slots->links() }}
        </div>
    </div>
</x-app-layout>
