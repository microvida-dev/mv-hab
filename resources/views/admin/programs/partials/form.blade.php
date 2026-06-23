@php
    $ruleRows = old('rules', isset($program) ? $program->rules->map(fn ($rule) => [
        'title' => $rule->title,
        'description' => $rule->description,
        'effective_from' => $rule->effective_from?->format('Y-m-d'),
        'effective_until' => $rule->effective_until?->format('Y-m-d'),
    ])->all() : []);

    $ruleRows = array_pad($ruleRows, max(3, count($ruleRows)), []);
@endphp

<div class="grid gap-6 lg:grid-cols-2">
    <div>
        <x-input-label for="municipality_id" value="Município" />
        <select id="municipality_id" name="municipality_id" class="mt-1 block w-full rounded-md border-ink-100 focus:border-civic-500 focus:ring-civic-500" required>
            <option value="">Selecionar município</option>
            @foreach ($municipalities as $municipality)
                <option value="{{ $municipality->id }}" @selected(old('municipality_id', $program->municipality_id ?? null) == $municipality->id)>{{ $municipality->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('municipality_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="name" value="Nome" />
        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $program->name ?? '')" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div class="lg:col-span-2">
        <x-input-label for="slug" value="Slug público (opcional)" />
        <x-text-input id="slug" name="slug" class="mt-1 block w-full" :value="old('slug', $program->slug ?? '')" />
        <p class="mt-1 text-xs text-ink-500">Se ficar vazio, será gerado a partir do nome.</p>
        <x-input-error :messages="$errors->get('slug')" class="mt-2" />
    </div>

    <div class="lg:col-span-2">
        <x-input-label for="summary" value="Resumo público" />
        <textarea id="summary" name="summary" rows="3" maxlength="500" class="mt-1 block w-full rounded-md border-ink-100 focus:border-civic-500 focus:ring-civic-500" required>{{ old('summary', $program->summary ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('summary')" class="mt-2" />
    </div>

    <div class="lg:col-span-2">
        <x-input-label for="description" value="Descrição pública" />
        <textarea id="description" name="description" rows="7" class="mt-1 block w-full rounded-md border-ink-100 focus:border-civic-500 focus:ring-civic-500" required>{{ old('description', $program->description ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div class="lg:col-span-2">
        <x-input-label for="legal_basis" value="Enquadramento legal" />
        <textarea id="legal_basis" name="legal_basis" rows="4" class="mt-1 block w-full rounded-md border-ink-100 focus:border-civic-500 focus:ring-civic-500">{{ old('legal_basis', $program->legal_basis ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('legal_basis')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="starts_at" value="Data de início" />
        <x-text-input id="starts_at" name="starts_at" type="date" class="mt-1 block w-full" :value="old('starts_at', isset($program) ? $program->starts_at?->format('Y-m-d') : '')" />
    </div>

    <div>
        <x-input-label for="ends_at" value="Data de fim" />
        <x-text-input id="ends_at" name="ends_at" type="date" class="mt-1 block w-full" :value="old('ends_at', isset($program) ? $program->ends_at?->format('Y-m-d') : '')" />
    </div>
</div>

<section class="mt-8 border-t border-ink-100 pt-6">
    <div>
        <h2 class="text-lg font-semibold text-ink-900">Regras públicas</h2>
        <p class="mt-1 text-sm text-ink-500">É necessária pelo menos uma regra para publicar o programa.</p>
    </div>

    <div class="mt-5 space-y-4">
        @foreach ($ruleRows as $index => $rule)
            <div class="rounded-lg border border-ink-100 bg-ink-50 p-4">
                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="lg:col-span-2">
                        <x-input-label :for="'rules_'.$index.'_title'" :value="'Regra '.($index + 1)" />
                        <x-text-input :id="'rules_'.$index.'_title'" :name="'rules['.$index.'][title]'" class="mt-1 block w-full" :value="$rule['title'] ?? ''" />
                    </div>
                    <div class="lg:col-span-2">
                        <textarea name="rules[{{ $index }}][description]" rows="3" class="block w-full rounded-md border-ink-100 focus:border-civic-500 focus:ring-civic-500" placeholder="Descrição da regra">{{ $rule['description'] ?? '' }}</textarea>
                    </div>
                    <div>
                        <x-input-label :for="'rules_'.$index.'_from'" value="Vigência desde" />
                        <x-text-input :id="'rules_'.$index.'_from'" :name="'rules['.$index.'][effective_from]'" type="date" class="mt-1 block w-full" :value="$rule['effective_from'] ?? ''" />
                    </div>
                    <div>
                        <x-input-label :for="'rules_'.$index.'_until'" value="Vigência até" />
                        <x-text-input :id="'rules_'.$index.'_until'" :name="'rules['.$index.'][effective_until]'" type="date" class="mt-1 block w-full" :value="$rule['effective_until'] ?? ''" />
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>

<div class="mt-8 flex justify-end gap-3">
    <a href="{{ route('admin.programs.index') }}" class="mv-button-secondary">Cancelar</a>
    <x-primary-button>{{ $submitLabel }}</x-primary-button>
</div>
