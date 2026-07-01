@php
    $selectedMimeTypes = old('allowed_mime_types', $documentType?->allowed_mime_types ?? ['application/pdf', 'image/jpeg', 'image/png', 'image/webp']);
@endphp

<div class="grid gap-5">
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="code" value="Código" />
            <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" :value="old('code', $documentType?->code)" required />
            <x-input-error class="mt-2" :messages="$errors->get('code')" />
        </div>
        <div>
            <x-input-label for="name" value="Nome" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $documentType?->name)" required />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>
    </div>

    <div>
        <x-input-label for="description" value="Descrição" />
        <textarea id="description" name="description" rows="3" class="mv-input mt-1 block w-full">{{ old('description', $documentType?->description) }}</textarea>
        <x-input-error class="mt-2" :messages="$errors->get('description')" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="category" value="Categoria" />
            <select id="category" name="category" class="mv-input mt-1 block w-full text-sm" required>
                @foreach ($categories as $value => $label)
                    <option value="{{ $value }}" @selected(old('category', $documentType?->category?->value) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('category')" />
        </div>
        <div>
            <x-input-label for="applies_to" value="Aplica-se a" />
            <select id="applies_to" name="applies_to" class="mv-input mt-1 block w-full text-sm" required>
                @foreach ($appliesTo as $value => $label)
                    <option value="{{ $value }}" @selected(old('applies_to', $documentType?->applies_to?->value) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('applies_to')" />
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="max_file_size_mb" value="Tamanho máximo (MB)" />
            <x-text-input id="max_file_size_mb" name="max_file_size_mb" type="number" min="1" max="25" class="mt-1 block w-full" :value="old('max_file_size_mb', $documentType?->max_file_size_mb ?? 10)" required />
            <x-input-error class="mt-2" :messages="$errors->get('max_file_size_mb')" />
        </div>
        <div>
            <x-input-label for="sort_order" value="Ordenação" />
            <x-text-input id="sort_order" name="sort_order" type="number" min="0" class="mt-1 block w-full" :value="old('sort_order', $documentType?->sort_order ?? 0)" />
            <x-input-error class="mt-2" :messages="$errors->get('sort_order')" />
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        @foreach ([
            'is_active' => 'Ativo',
            'is_required_by_default' => 'Obrigatório por defeito',
            'requires_issue_date' => 'Exige data de emissão',
            'requires_expiry_date' => 'Exige data de validade',
        ] as $field => $label)
            <label class="flex items-center gap-2 rounded-2xl border border-mvhab-support/30 bg-mvhab-surface p-3 text-sm text-ink-700">
                <input type="hidden" name="{{ $field }}" value="0">
                <input type="checkbox" name="{{ $field }}" value="1" class="mv-checkbox" @checked(old($field, $documentType?->{$field} ?? ($field === 'is_active')))>
                <span>{{ $label }}</span>
            </label>
        @endforeach
    </div>

    <div>
        <x-input-label value="Mime types permitidos" />
        <div class="mt-2 grid gap-2 sm:grid-cols-2">
            @foreach ($mimeTypes as $mime => $label)
                <label class="flex items-center gap-2 rounded-2xl border border-mvhab-support/30 bg-mvhab-surface p-3 text-sm text-ink-700">
                    <input type="checkbox" name="allowed_mime_types[]" value="{{ $mime }}" class="mv-checkbox" @checked(in_array($mime, $selectedMimeTypes ?? [], true))>
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </div>
        <x-input-error class="mt-2" :messages="$errors->get('allowed_mime_types')" />
    </div>
</div>
