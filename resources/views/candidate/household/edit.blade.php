<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-civic-700">Agregado familiar</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Editar dados gerais</h1>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('candidate.household.update') }}" class="mv-surface space-y-6 p-6">
                @csrf
                @method('PUT')

                <div>
                    <x-input-label for="name" value="Nome do agregado" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $household->name)" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="household_type" value="Tipo de agregado" />
                    <select id="household_type" name="household_type" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" required>
                        <option value="family" @selected(old('household_type', $household->household_type) === 'family')>Agregado familiar</option>
                        <option value="single_person" @selected(old('household_type', $household->household_type) === 'single_person')>Pessoa isolada</option>
                        <option value="other" @selected(old('household_type', $household->household_type) === 'other')>Outra composição</option>
                    </select>
                    <x-input-error :messages="$errors->get('household_type')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="notes" value="Observações" />
                    <textarea id="notes" name="notes" rows="4" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">{{ old('notes', $household->notes) }}</textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>

                <div class="flex flex-wrap justify-end gap-3">
                    <a href="{{ route('candidate.household.show') }}" class="mv-button-secondary">Voltar</a>
                    <button type="submit" class="mv-button-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
