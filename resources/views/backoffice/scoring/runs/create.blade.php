<x-app-layout>
    <x-slot name="header"><div><p class="text-sm font-semibold text-mvhab-primary">Classificação</p><h1 class="mt-1 text-2xl font-semibold text-ink-900">Executar classificação</h1></div></x-slot>
    <div class="py-8"><div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
            A execução da classificação irá calcular pontuações com base nos dados existentes no sistema. Confirme que as candidaturas admitidas para classificação e a matriz de classificação estão corretas antes de prosseguir.
        </div>
        <div class="mv-surface p-6">
            <form method="POST" action="{{ route('backoffice.scoring.runs.store') }}" class="space-y-6">
                @csrf
                <div class="grid gap-5 md:grid-cols-2">
                    <div><x-input-label for="program_id" value="Programa" /><select id="program_id" name="program_id" class="mv-input mt-1 block w-full"><option value="">Selecionar por concurso</option>@foreach ($programs as $program)<option value="{{ $program->id }}" @selected((int) old('program_id', request('program_id')) === $program->id)>{{ $program->name }}</option>@endforeach</select><x-input-error :messages="$errors->get('program_id')" class="mt-2" /></div>
                    <div><x-input-label for="contest_id" value="Concurso" /><select id="contest_id" name="contest_id" class="mv-input mt-1 block w-full"><option value="">Sem concurso específico</option>@foreach ($contests as $contest)<option value="{{ $contest->id }}" @selected((int) old('contest_id', request('contest_id')) === $contest->id)>{{ $contest->title }}</option>@endforeach</select><x-input-error :messages="$errors->get('contest_id')" class="mt-2" /></div>
                </div>
                <div><x-input-label for="scoring_rule_set_id" value="Matriz aplicável opcional" /><select id="scoring_rule_set_id" name="scoring_rule_set_id" class="mv-input mt-1 block w-full"><option value="">Resolver automaticamente</option>@foreach ($ruleSets as $ruleSet)<option value="{{ $ruleSet->id }}" @selected((int) old('scoring_rule_set_id', request('scoring_rule_set_id')) === $ruleSet->id)>{{ $ruleSet->name }}</option>@endforeach</select><x-input-error :messages="$errors->get('scoring_rule_set_id')" class="mt-2" /></div>
                <div><x-input-label for="notes" value="Notas internas" /><textarea id="notes" name="notes" rows="3" class="mv-input mt-1 block w-full">{{ old('notes') }}</textarea></div>
                <button class="mv-button-primary">Executar</button>
            </form>
        </div>
    </div></div>
</x-app-layout>
