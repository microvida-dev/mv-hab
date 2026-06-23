@php
    $fieldValue = fn (string $field, mixed $default = null) => old($field, $registration?->{$field} ?? $default);
    $dateValue = function (string $field) use ($fieldValue): mixed {
        $value = $fieldValue($field);

        return $value instanceof \Carbon\CarbonInterface ? $value->format('Y-m-d') : $value;
    };
@endphp

<div class="space-y-8">
    <section>
        <h2 class="text-base font-semibold text-ink-900">Dados pessoais</h2>
        <div class="mt-4 grid gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <x-input-label for="full_name" value="Nome completo *" />
                <x-text-input id="full_name" name="full_name" class="mt-1 block w-full" :value="$fieldValue('full_name')" autocomplete="name" />
                <x-input-error :messages="$errors->get('full_name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="birth_date" value="Data de nascimento *" />
                <x-text-input id="birth_date" name="birth_date" type="date" class="mt-1 block w-full" :value="$dateValue('birth_date')" />
                <x-input-error :messages="$errors->get('birth_date')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="nationality" value="Nacionalidade" />
                <x-text-input id="nationality" name="nationality" class="mt-1 block w-full" :value="$fieldValue('nationality')" />
                <x-input-error :messages="$errors->get('nationality')" class="mt-2" />
            </div>
        </div>
    </section>

    <section class="border-t border-ink-100 pt-7">
        <h2 class="text-base font-semibold text-ink-900">Identificação</h2>
        <div class="mt-4 grid gap-5 md:grid-cols-2">
            <div>
                <x-input-label for="document_type" value="Tipo de documento" />
                <select id="document_type" name="document_type" class="mt-1 block w-full rounded-md border-ink-100 focus:border-civic-500 focus:ring-civic-500">
                    <option value="">Selecionar</option>
                    @foreach (['Cartão de Cidadão', 'Passaporte', 'Título de residência', 'Outro'] as $type)
                        <option value="{{ $type }}" @selected($fieldValue('document_type') === $type)>{{ $type }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('document_type')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="document_number" value="Número do documento" />
                <x-text-input id="document_number" name="document_number" class="mt-1 block w-full" :value="$fieldValue('document_number')" />
                <x-input-error :messages="$errors->get('document_number')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="document_valid_until" value="Validade do documento" />
                <x-text-input id="document_valid_until" name="document_valid_until" type="date" class="mt-1 block w-full" :value="$dateValue('document_valid_until')" />
                <x-input-error :messages="$errors->get('document_valid_until')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="nif" value="NIF *" />
                <x-text-input id="nif" name="nif" class="mt-1 block w-full" :value="$fieldValue('nif')" inputmode="numeric" />
                <x-input-error :messages="$errors->get('nif')" class="mt-2" />
            </div>
        </div>
    </section>

    <section class="border-t border-ink-100 pt-7">
        <h2 class="text-base font-semibold text-ink-900">Contactos</h2>
        <div class="mt-4 grid gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <x-input-label for="email" value="Email *" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="$fieldValue('email', Auth::user()->email)" autocomplete="email" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="phone" value="Telefone" />
                <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="$fieldValue('phone')" autocomplete="tel" />
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="mobile_phone" value="Telemóvel" />
                <x-text-input id="mobile_phone" name="mobile_phone" class="mt-1 block w-full" :value="$fieldValue('mobile_phone')" autocomplete="tel" />
                <x-input-error :messages="$errors->get('mobile_phone')" class="mt-2" />
            </div>
        </div>
    </section>

    <section class="border-t border-ink-100 pt-7">
        <h2 class="text-base font-semibold text-ink-900">Morada</h2>
        <div class="mt-4 grid gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <x-input-label for="address" value="Morada *" />
                <x-text-input id="address" name="address" class="mt-1 block w-full" :value="$fieldValue('address')" autocomplete="street-address" />
                <x-input-error :messages="$errors->get('address')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="postal_code" value="Código postal *" />
                <x-text-input id="postal_code" name="postal_code" class="mt-1 block w-full" :value="$fieldValue('postal_code')" autocomplete="postal-code" />
                <x-input-error :messages="$errors->get('postal_code')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="city" value="Localidade *" />
                <x-text-input id="city" name="city" class="mt-1 block w-full" :value="$fieldValue('city')" />
                <x-input-error :messages="$errors->get('city')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="parish" value="Freguesia" />
                <x-text-input id="parish" name="parish" class="mt-1 block w-full" :value="$fieldValue('parish')" />
                <x-input-error :messages="$errors->get('parish')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="municipality" value="Município *" />
                <x-text-input id="municipality" name="municipality" class="mt-1 block w-full" :value="$fieldValue('municipality')" />
                <x-input-error :messages="$errors->get('municipality')" class="mt-2" />
            </div>
        </div>
    </section>

    <section class="border-t border-ink-100 pt-7">
        <h2 class="text-base font-semibold text-ink-900">Preferências de notificação</h2>
        <div class="mt-4 grid gap-3">
            @foreach ([
                'wants_email_notifications' => ['Email', true],
                'wants_sms_notifications' => ['SMS', false],
                'wants_postal_notifications' => ['Via postal', false],
            ] as $field => [$label, $default])
                <label class="flex items-center gap-3 rounded-md border border-ink-100 px-4 py-3 text-sm text-ink-700">
                    <input type="checkbox" name="{{ $field }}" value="1" class="rounded border-ink-300 text-civic-700 focus:ring-civic-500" @checked(old($field, $registration?->{$field} ?? $default))>
                    Pretendo receber notificações por {{ $label }}.
                </label>
            @endforeach
        </div>
    </section>

    <section class="border-t border-ink-100 pt-7">
        <h2 class="text-base font-semibold text-ink-900">Consentimentos</h2>
        <p class="mt-2 text-sm leading-6 text-ink-500">Os dados recolhidos destinam-se à gestão do Registo de Adesão e à preparação de futuras candidaturas a programas municipais de habitação. O tratamento será realizado nos termos da legislação aplicável e das finalidades definidas pelo município.</p>
        <div class="mt-4 grid gap-3">
            <label class="flex items-start gap-3 rounded-md border border-ink-100 px-4 py-3 text-sm text-ink-700">
                <input type="checkbox" name="accepts_terms" value="1" class="mt-0.5 rounded border-ink-300 text-civic-700 focus:ring-civic-500" @checked(old('accepts_terms', $registration?->accepts_terms ?? false))>
                <span>Declaro que li e aceito os termos de utilização. *</span>
            </label>
            <x-input-error :messages="$errors->get('accepts_terms')" />

            <label class="flex items-start gap-3 rounded-md border border-ink-100 px-4 py-3 text-sm text-ink-700">
                <input type="checkbox" name="accepts_data_processing" value="1" class="mt-0.5 rounded border-ink-300 text-civic-700 focus:ring-civic-500" @checked(old('accepts_data_processing', $registration?->accepts_data_processing ?? false))>
                <span>Confirmo a informação sobre o tratamento dos meus dados para gestão do Registo de Adesão e futuras candidaturas. *</span>
            </label>
            <x-input-error :messages="$errors->get('accepts_data_processing')" />
        </div>
    </section>

    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-ink-100 pt-6">
        <a href="{{ route('candidate.registration.show') }}" class="mv-button-secondary">Cancelar edição</a>
        <button type="submit" class="mv-button-primary">{{ $submitLabel }}</button>
    </div>
</div>
