<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold text-mvhab-primary">Simulador</p>
            <h1 class="mt-1 text-2xl font-semibold text-ink-900">Nova simulação</h1>
            <p class="mt-1 text-sm text-ink-500">{{ $notices['short'] }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('candidate.simulations.store') }}" class="mv-surface space-y-6 p-6">
                @csrf
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="text-sm font-semibold text-ink-800">Concurso</span>
                        <select name="contest_id" class="mv-input mt-1 w-full">
                            <option value="">Recomendar automaticamente</option>
                            @foreach ($contests as $contest)
                                <option value="{{ $contest->id }}" @selected(old('contest_id') == $contest->id)>{{ $contest->title }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-ink-800">Situação habitacional</span>
                        <input name="housing_status" value="{{ old('housing_status', 'registration') }}" class="mv-input mt-1 w-full" required>
                    </label>
                    <label class="block"><span class="text-sm font-semibold text-ink-800">Elementos</span><input type="number" min="1" max="20" name="household_members_count" value="{{ old('household_members_count', 1) }}" class="mv-input mt-1 w-full" required></label>
                    <label class="block"><span class="text-sm font-semibold text-ink-800">Adultos</span><input type="number" min="1" max="20" name="adults_count" value="{{ old('adults_count', 1) }}" class="mv-input mt-1 w-full"></label>
                    <label class="block"><span class="text-sm font-semibold text-ink-800">Dependentes</span><input type="number" min="0" max="20" name="dependents_count" value="{{ old('dependents_count', 0) }}" class="mv-input mt-1 w-full"></label>
                    <label class="block"><span class="text-sm font-semibold text-ink-800">Rendimento mensal</span><input type="number" min="0" step="0.01" name="monthly_income" value="{{ old('monthly_income') }}" class="mv-input mt-1 w-full" required></label>
                </div>
                <label class="flex items-start gap-2 text-sm text-ink-700">
                    <input type="checkbox" name="privacy_notice_accepted" value="1" class="mv-checkbox mt-1" required>
                    <span>Confirmo que a simulação é indicativa.</span>
                </label>
                <div class="flex justify-end"><button class="mv-button-primary">Simular</button></div>
            </form>
        </div>
    </div>
</x-app-layout>
