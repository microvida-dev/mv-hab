<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Backoffice"
            title="Marcações públicas"
            description="Acompanhe visitas Open House efetuadas sem candidatura ou autenticação."
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('backoffice.housing-visits.index') }}" class="mv-button-secondary">
                    Visitas legacy
                </a>
                <a href="{{ route('backoffice.visit-slots.index') }}" class="mv-button-secondary">
                    Horários
                </a>
            </div>

            <form method="GET" action="{{ route('backoffice.public-visit-bookings.index') }}" class="mv-card flex flex-wrap items-end gap-4 p-5">
                <div class="min-w-56 flex-1">
                    <label for="status" class="mv-data-label">Estado</label>
                    <select id="status" name="status" class="mv-input mt-2">
                        <option value="">Todos os estados</option>
                        @foreach ($statusOptions as $statusOption)
                            <option value="{{ $statusOption->value }}" @selected($status === $statusOption->value)>
                                {{ $statusOption->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="mv-button-primary">Filtrar</button>
                @if ($status !== '')
                    <a href="{{ route('backoffice.public-visit-bookings.index') }}" class="mv-button-secondary">Limpar</a>
                @endif
            </form>

            <x-mv.section title="Marcações" padding="p-0" class="overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-ink-100 text-sm">
                        <thead class="bg-ink-50 text-left text-xs font-semibold uppercase text-ink-500">
                            <tr>
                                <th class="px-5 py-3">Referência</th>
                                <th class="px-5 py-3">Fogo</th>
                                <th class="px-5 py-3">Data</th>
                                <th class="px-5 py-3">Participantes</th>
                                <th class="px-5 py-3">Estado</th>
                                <th class="px-5 py-3 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @forelse ($bookings as $booking)
                                <tr>
                                    <td class="px-5 py-4 font-semibold text-ink-900">{{ $booking->booking_reference }}</td>
                                    <td class="px-5 py-4 text-ink-700">{{ $booking->housingUnit?->displayTitle() }}</td>
                                    <td class="px-5 py-4 text-ink-700">{{ $booking->slot?->starts_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="px-5 py-4 text-ink-700">{{ $booking->guest_count }}</td>
                                    <td class="px-5 py-4"><x-mv.badge>{{ $booking->status->label() }}</x-mv.badge></td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('backoffice.public-visit-bookings.show', $booking) }}" class="font-semibold text-mvhab-primary">Consultar</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-5 py-8 text-center text-ink-500">Sem marcações públicas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-mv.section>

            {{ $bookings->links() }}
        </div>
    </div>
</x-app-layout>
