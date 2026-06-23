<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Visitas</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Reagendar visita</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('candidate.visits.reschedule.store', $visit) }}" class="mv-surface space-y-5 p-6">
                @csrf
                <div>
                    <x-input-label for="new_visit_slot_id" value="Novo horário" />
                    <select id="new_visit_slot_id" name="new_visit_slot_id" class="mt-1 w-full rounded-md border-ink-300 text-sm" required>
                        <option value="">Selecionar horário</option>
                        @foreach ($slots as $slot)
                            <option value="{{ $slot->id }}" @selected(old('new_visit_slot_id') == $slot->id)>{{ $slot->starts_at?->format('d/m/Y H:i') }} · {{ $slot->location ?? 'Disponibilidade municipal' }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('new_visit_slot_id')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="reason" value="Motivo" />
                    <textarea id="reason" name="reason" rows="3" class="mt-1 w-full rounded-md border-ink-300 text-sm">{{ old('reason') }}</textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <a href="{{ route('candidate.visits.show', $visit) }}" class="mv-button-secondary">Voltar</a>
                    <button type="submit" class="mv-button-primary">Reagendar</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
