<x-app-layout>
    <x-slot name="header">
        <x-mv.page-header
            eyebrow="Área do candidato"
            title="Escolher preferências"
        />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <form method="POST" action="{{ route('candidate.housing-preferences.update', $application) }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <x-mv.section title="Habitações disponíveis">
                    <div class="space-y-3">
                        @forelse($availableUnits as $index => $unit)
                            @php($existing = $application->housingPreferences->firstWhere('contest_housing_unit_id', $unit->id))
                            <div class="grid gap-3 rounded-2xl border border-ink-100 p-3 md:grid-cols-[1fr,120px]">
                                <input type="hidden" name="preferences[{{ $index }}][contest_housing_unit_id]" value="{{ $unit->id }}">
                                <div>
                                    <p class="font-semibold">{{ $unit->housingUnit?->code }} · {{ $unit->housingUnit?->address }}</p>
                                    <p class="text-sm text-ink-500">{{ $unit->typology ?? $unit->housingUnit?->typology }} · {{ $unit->max_occupants ?? '-' }} ocupantes</p>
                                </div>
                                <label class="text-sm font-medium text-ink-700">Ordem<input type="number" name="preferences[{{ $index }}][preference_order]" value="{{ old('preferences.'.$index.'.preference_order', $existing?->preference_order ?? $index + 1) }}" min="1" class="mv-input mt-1 w-full"></label>
                            </div>
                        @empty
                            <x-mv.alert>
                                Não existem habitações disponíveis para este concurso.
                            </x-mv.alert>
                        @endforelse
                    </div>
                </x-mv.section>

                <div class="flex flex-wrap justify-end gap-2">
                    <button class="mv-button-secondary">Guardar</button>
                </div>
            </form>

            @if($availableUnits->isNotEmpty())
                <form method="POST" action="{{ route('candidate.housing-preferences.submit', $application) }}">
                    @csrf
                    <x-mv.section
                        title="Submeter preferências"
                        description="Confirme a ordenação antes de submeter as preferências para análise municipal."
                    >
                        @foreach($availableUnits as $index => $unit)
                            @php($existing = $application->housingPreferences->firstWhere('contest_housing_unit_id', $unit->id))
                            <input type="hidden" name="preferences[{{ $index }}][contest_housing_unit_id]" value="{{ $unit->id }}">
                            <input type="hidden" name="preferences[{{ $index }}][preference_order]" value="{{ $existing?->preference_order ?? $index + 1 }}">
                        @endforeach
                        <button class="mv-button-primary">Submeter preferências</button>
                    </x-mv.section>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
