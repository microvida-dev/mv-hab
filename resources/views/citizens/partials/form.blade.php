<div class="grid gap-6 md:grid-cols-2">
    <div>
        <x-input-label for="name" value="Nome" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $citizen->name ?? '')" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="document_number" value="N.º de documento" />
        <x-text-input id="document_number" name="document_number" type="text" class="mt-1 block w-full" :value="old('document_number', $citizen->document_number ?? '')" required />
        <x-input-error :messages="$errors->get('document_number')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="birth_date" value="Data de nascimento" />
        <x-text-input id="birth_date" name="birth_date" type="date" class="mt-1 block w-full" :value="old('birth_date', isset($citizen) ? $citizen->birth_date?->format('Y-m-d') : '')" required />
        <x-input-error :messages="$errors->get('birth_date')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="phone" value="Telefone" />
        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $citizen->phone ?? '')" />
        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="email" value="Email" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $citizen->email ?? '')" />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="address" value="Morada" />
        <x-text-input id="address" name="address" type="text" class="mt-1 block w-full" :value="old('address', $citizen->address ?? '')" />
        <x-input-error :messages="$errors->get('address')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <x-input-label for="notes" value="Notas" />
        <textarea id="notes" name="notes" rows="4" class="mv-input mt-1">{{ old('notes', $citizen->notes ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
    </div>
</div>
