<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-mvhab-primary">Visitas</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Agendar visita</h1>
            <p class="mt-1 text-sm text-ink-500">{{ $notice }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('candidate.visits.store') }}" class="mv-surface space-y-5 p-6">
                @csrf

                <x-ui.field for="visit_slot_id" name="visit_slot_id" label="Horário disponível" required>
                    <x-ui.select id="visit_slot_id" name="visit_slot_id" required>
                        <option value="">Selecionar horário</option>
                        @foreach ($slots as $slot)
                            <option value="{{ $slot->id }}" @selected(old('visit_slot_id') == $slot->id)>
                                {{ $slot->starts_at?->format('d/m/Y H:i') }} · {{ $slot->housingUnit?->title ?? $slot->contest?->title ?? $slot->location ?? 'Disponibilidade municipal' }} · {{ $slot->remainingCapacity() }} vagas
                            </option>
                        @endforeach
                    </x-ui.select>
                </x-ui.field>

                <x-ui.field for="application_id" name="application_id" label="Candidatura associada">
                    <x-ui.select id="application_id" name="application_id">
                        <option value="">Sem candidatura associada</option>
                        @foreach ($applications as $application)
                            <option value="{{ $application->id }}" @selected(old('application_id') == $application->id)>
                                {{ $application->application_number ?? 'Rascunho' }} · {{ $application->contest->title }}
                            </option>
                        @endforeach
                    </x-ui.select>
                </x-ui.field>

                <x-ui.field for="candidate_notes" name="candidate_notes" label="Notas para os serviços">
                    <x-ui.textarea id="candidate_notes" name="candidate_notes" rows="4">{{ old('candidate_notes') }}</x-ui.textarea>
                </x-ui.field>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('candidate.visits.index') }}" class="mv-button-secondary">
                        Cancelar
                    </a>

                    <button type="submit" class="mv-button-primary">
                        Pedir agendamento
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
