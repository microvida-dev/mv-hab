<div class="grid gap-5">
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="document_type_id" value="Tipo documental" />
            <select id="document_type_id" name="document_type_id" class="mv-input mt-1 block w-full text-sm" required>
                @foreach ($documentTypes as $documentType)
                    <option value="{{ $documentType->id }}" @selected(old('document_type_id', $requiredDocument?->document_type_id) == $documentType->id)>{{ $documentType->name }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('document_type_id')" />
        </div>
        <div>
            <x-input-label for="required_for" value="Obrigatório para" />
            <select id="required_for" name="required_for" class="mv-input mt-1 block w-full text-sm" required>
                @foreach ($requiredFor as $value => $label)
                    <option value="{{ $value }}" @selected(old('required_for', $requiredDocument?->required_for?->value) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('required_for')" />
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="program_id" value="Programa opcional" />
            <select id="program_id" name="program_id" class="mv-input mt-1 block w-full text-sm">
                <option value="">Global</option>
                @foreach ($programs as $program)
                    <option value="{{ $program->id }}" @selected(old('program_id', $requiredDocument?->program_id) == $program->id)>{{ $program->name }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('program_id')" />
        </div>
        <div>
            <x-input-label for="contest_id" value="Concurso opcional" />
            <select id="contest_id" name="contest_id" class="mv-input mt-1 block w-full text-sm">
                <option value="">Global ou programa</option>
                @foreach ($contests as $contest)
                    <option value="{{ $contest->id }}" @selected(old('contest_id', $requiredDocument?->contest_id) == $contest->id)>{{ $contest->title }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('contest_id')" />
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div>
            <x-input-label for="condition_key" value="Condição" />
            <x-text-input id="condition_key" name="condition_key" type="text" class="mt-1 block w-full" :value="old('condition_key', $requiredDocument?->condition_key ?? 'always')" required />
            <x-input-error class="mt-2" :messages="$errors->get('condition_key')" />
        </div>
        <div>
            <x-input-label for="condition_operator" value="Operador" />
            <select id="condition_operator" name="condition_operator" class="mv-input mt-1 block w-full text-sm" required>
                @foreach ($operators as $value => $label)
                    <option value="{{ $value }}" @selected(old('condition_operator', $requiredDocument?->condition_operator?->value ?? 'always') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('condition_operator')" />
        </div>
        <div>
            <x-input-label for="condition_value" value="Valor" />
            <x-text-input id="condition_value" name="condition_value" type="text" class="mt-1 block w-full" :value="old('condition_value', $requiredDocument?->condition_value)" />
            <x-input-error class="mt-2" :messages="$errors->get('condition_value')" />
        </div>
    </div>

    <div>
        <x-input-label for="instructions" value="Instruções para o candidato" />
        <textarea id="instructions" name="instructions" rows="3" class="mv-input mt-1 block w-full">{{ old('instructions', $requiredDocument?->instructions) }}</textarea>
        <x-input-error class="mt-2" :messages="$errors->get('instructions')" />
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <label class="flex items-center gap-2 rounded-2xl border border-mvhab-support/30 bg-mvhab-surface p-3 text-sm text-ink-700">
            <input type="hidden" name="is_required" value="0">
            <input type="checkbox" name="is_required" value="1" class="mv-checkbox" @checked(old('is_required', $requiredDocument?->is_required ?? true))>
            <span>Obrigatório</span>
        </label>
        <label class="flex items-center gap-2 rounded-2xl border border-mvhab-support/30 bg-mvhab-surface p-3 text-sm text-ink-700">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="mv-checkbox" @checked(old('is_active', $requiredDocument?->is_active ?? true))>
            <span>Ativo</span>
        </label>
        <div>
            <x-input-label for="sort_order" value="Ordenação" />
            <x-text-input id="sort_order" name="sort_order" type="number" min="0" class="mt-1 block w-full" :value="old('sort_order', $requiredDocument?->sort_order ?? 0)" />
            <x-input-error class="mt-2" :messages="$errors->get('sort_order')" />
        </div>
    </div>
</div>
