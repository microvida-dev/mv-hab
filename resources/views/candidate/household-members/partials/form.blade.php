@php
    $member = $member ?? null;
@endphp

<div class="space-y-8">
    <section>
        <h2 class="text-base font-semibold text-ink-900">Identificação</h2>
        <div class="mt-4 grid gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <x-input-label for="full_name" value="Nome completo *" />
                <x-text-input id="full_name" name="full_name" class="mt-1 block w-full" :value="old('full_name', $member?->full_name)" required />
                <x-input-error :messages="$errors->get('full_name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="birth_date" value="Data de nascimento *" />
                <x-text-input id="birth_date" name="birth_date" type="date" class="mt-1 block w-full" :value="old('birth_date', $member?->birth_date?->toDateString())" required />
                <x-input-error :messages="$errors->get('birth_date')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="relationship" value="Relação com o requerente *" />
                <select id="relationship" name="relationship" class="mv-input mt-1 w-full" required>
                    <option value="">Selecione</option>
                    @foreach ($relationships as $value => $label)
                        <option value="{{ $value }}" @selected(old('relationship', $member?->relationship?->value) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('relationship')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="gender" value="Género" />
                <select id="gender" name="gender" class="mv-input mt-1 w-full">
                    <option value="">Prefiro não indicar</option>
                    @foreach (['female' => 'Feminino', 'male' => 'Masculino', 'non_binary' => 'Não binário', 'other' => 'Outro'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('gender', $member?->gender) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="nationality" value="Nacionalidade" />
                <x-text-input id="nationality" name="nationality" class="mt-1 block w-full" :value="old('nationality', $member?->nationality)" />
            </div>
            <div>
                <x-input-label for="nif" value="NIF" />
                <x-text-input id="nif" name="nif" class="mt-1 block w-full" :value="old('nif', $member?->nif)" />
                <x-input-error :messages="$errors->get('nif')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="marital_status" value="Estado civil" />
                <x-text-input id="marital_status" name="marital_status" class="mt-1 block w-full" :value="old('marital_status', $member?->marital_status)" />
            </div>
        </div>
    </section>

    <section class="border-t border-ink-100 pt-7">
        <h2 class="text-base font-semibold text-ink-900">Situação profissional</h2>
        <div class="mt-4 grid gap-5 md:grid-cols-2">
            <div>
                <x-input-label for="professional_status" value="Situação profissional" />
                <select id="professional_status" name="professional_status" class="mv-input mt-1 w-full">
                    <option value="">Selecione</option>
                    @foreach ($professionalStatuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('professional_status', $member?->professional_status?->value) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="qualification_level" value="Nível de qualificação (QNQ)" />
                <select id="qualification_level" name="qualification_level" class="mv-input mt-1 w-full">
                    <option value="">Selecione</option>
                    @foreach (range(1, 8) as $level)
                        <option value="{{ $level }}" @selected((string) old('qualification_level', $member?->qualification_level) === (string) $level)>
                            Nível {{ $level }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('qualification_level')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="employment_type" value="Tipo de vínculo" />
                <x-text-input id="employment_type" name="employment_type" class="mt-1 block w-full" :value="old('employment_type', $member?->employment_type)" />
            </div>
            <div>
                <x-input-label for="employer_name" value="Entidade empregadora" />
                <x-text-input id="employer_name" name="employer_name" class="mt-1 block w-full" :value="old('employer_name', $member?->employer_name)" />
            </div>
            <div>
                <x-input-label for="workplace_municipality" value="Município do local de trabalho" />
                <x-text-input id="workplace_municipality" name="workplace_municipality" class="mt-1 block w-full" :value="old('workplace_municipality', $member?->workplace_municipality)" />
            </div>
        </div>
    </section>

    <section class="border-t border-ink-100 pt-7">
        <h2 class="text-base font-semibold text-ink-900">Condições do membro</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            @foreach ([
                'works_in_municipality' => 'Trabalha no município',
                'is_dependent' => 'É dependente',
                'is_student' => 'É estudante',
                'is_disabled' => 'Tem deficiência ou incapacidade',
                'has_multiple_disabilities' => 'Tem multideficiência',
                'is_pregnant' => 'Está grávida',
                'has_reduced_mobility' => 'Tem mobilidade reduzida',
                'is_informal_caregiver' => 'É cuidador informal',
                'has_no_income' => 'Não possui rendimentos',
                'is_exempt_from_irs' => 'Dispensado de entregar IRS',
            ] as $field => $label)
                <label class="flex items-start gap-3 rounded-2xl border border-ink-100 p-3 text-sm text-ink-700">
                    <input type="checkbox" name="{{ $field }}" value="1" class="mv-checkbox mt-0.5" @checked(old($field, $member?->{$field} ?? false))>
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </div>

        <div class="mt-5 grid gap-5 md:grid-cols-2">
            <div>
                <x-input-label for="disability_percentage" value="Percentagem de incapacidade" />
                <x-text-input id="disability_percentage" name="disability_percentage" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full" :value="old('disability_percentage', $member?->disability_percentage)" />
                <x-input-error :messages="$errors->get('disability_percentage')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="no_income_reason" value="Motivo da ausência de rendimentos" />
                <x-text-input id="no_income_reason" name="no_income_reason" class="mt-1 block w-full" :value="old('no_income_reason', $member?->no_income_reason)" />
                <x-input-error :messages="$errors->get('no_income_reason')" class="mt-2" />
            </div>
        </div>
    </section>

    <section class="border-t border-ink-100 pt-7">
        <x-input-label for="notes" value="Observações" />
        <textarea id="notes" name="notes" rows="4" class="mv-input mt-1 w-full">{{ old('notes', $member?->notes) }}</textarea>
        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
    </section>
</div>
