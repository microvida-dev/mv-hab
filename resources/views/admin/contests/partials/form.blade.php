@php
    $deadlineRows = old('deadlines', isset($contest) ? $contest->deadlines->map(fn ($deadline) => [
        'type' => $deadline->type->value,
        'label' => $deadline->label,
        'starts_at' => $deadline->starts_at?->format('Y-m-d\TH:i'),
        'ends_at' => $deadline->ends_at->format('Y-m-d\TH:i'),
        'description' => $deadline->description,
    ])->all() : []);
    $deadlineRows = array_pad($deadlineRows, max(3, count($deadlineRows)), []);

    $juryRows = old('jury_members', isset($contest) ? $contest->juryMembers->map(fn ($member) => [
        'user_id' => $member->user_id,
        'role_in_jury' => $member->role_in_jury,
    ])->all() : []);
    $juryRows = array_pad($juryRows, max(2, count($juryRows)), []);
@endphp

<div class="grid gap-6 lg:grid-cols-2">
    <div>
        <x-input-label for="program_id" value="Programa" />
        <select id="program_id" name="program_id" class="mv-input mt-1 block w-full" required>
            <option value="">Selecionar programa</option>
            @foreach ($programs as $programOption)
                <option value="{{ $programOption->id }}" @selected(old('program_id', $contest->program_id ?? request('program_id')) == $programOption->id)>
                    {{ $programOption->name }} · {{ $programOption->status->label() }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('program_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="code" value="Código público" />
        <x-text-input id="code" name="code" class="mt-1 block w-full" :value="old('code', $contest->code ?? '')" required />
        <x-input-error :messages="$errors->get('code')" class="mt-2" />
    </div>

    <div class="lg:col-span-2">
        <x-input-label for="title" value="Título" />
        <x-text-input id="title" name="title" class="mt-1 block w-full" :value="old('title', $contest->title ?? '')" required />
    </div>

    <div class="lg:col-span-2">
        <x-input-label for="slug" value="Slug público (opcional)" />
        <x-text-input id="slug" name="slug" class="mt-1 block w-full" :value="old('slug', $contest->slug ?? '')" />
    </div>

    <div class="lg:col-span-2">
        <x-input-label for="summary" value="Resumo público" />
        <textarea id="summary" name="summary" rows="3" maxlength="500" class="mv-input mt-1 block w-full" required>{{ old('summary', $contest->summary ?? '') }}</textarea>
    </div>

    <div class="lg:col-span-2">
        <x-input-label for="description" value="Descrição pública" />
        <textarea id="description" name="description" rows="7" class="mv-input mt-1 block w-full" required>{{ old('description', $contest->description ?? '') }}</textarea>
    </div>

    <div class="lg:col-span-2">
        <x-input-label for="application_instructions" value="Instruções de preparação" />
        <textarea id="application_instructions" name="application_instructions" rows="5" class="mv-input mt-1 block w-full">{{ old('application_instructions', $contest->application_instructions ?? '') }}</textarea>
    </div>

    <div>
        <x-input-label for="opens_at" value="Abertura" />
        <x-text-input id="opens_at" name="opens_at" type="datetime-local" class="mt-1 block w-full" :value="old('opens_at', isset($contest) ? $contest->opens_at->format('Y-m-d\TH:i') : '')" required />
    </div>

    <div>
        <x-input-label for="closes_at" value="Encerramento" />
        <x-text-input id="closes_at" name="closes_at" type="datetime-local" class="mt-1 block w-full" :value="old('closes_at', isset($contest) ? $contest->closes_at->format('Y-m-d\TH:i') : '')" required />
    </div>
</div>

<section class="mt-8 border-t border-ink-100 pt-6">
    <h2 class="text-lg font-semibold text-ink-900">Prazos públicos</h2>
    <p class="mt-1 text-sm text-ink-500">É necessário pelo menos um prazo para publicar o concurso.</p>

    <div class="mt-5 space-y-4">
        @foreach ($deadlineRows as $index => $deadline)
            <div class="rounded-2xl border border-ink-100 bg-ink-50 p-4">
                <div class="grid gap-4 lg:grid-cols-2">
                    <div>
                        <x-input-label :for="'deadline_'.$index.'_type'" :value="'Prazo '.($index + 1)" />
                        <select id="deadline_{{ $index }}_type" name="deadlines[{{ $index }}][type]" class="mv-input mt-1 block w-full">
                            <option value="">Selecionar tipo</option>
                            @foreach ($deadlineTypes as $value => $label)
                                <option value="{{ $value }}" @selected(($deadline['type'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label :for="'deadline_'.$index.'_label'" value="Designação" />
                        <x-text-input :id="'deadline_'.$index.'_label'" :name="'deadlines['.$index.'][label]'" class="mt-1 block w-full" :value="$deadline['label'] ?? ''" />
                    </div>
                    <div>
                        <x-input-label :for="'deadline_'.$index.'_starts'" value="Início" />
                        <x-text-input :id="'deadline_'.$index.'_starts'" :name="'deadlines['.$index.'][starts_at]'" type="datetime-local" class="mt-1 block w-full" :value="$deadline['starts_at'] ?? ''" />
                    </div>
                    <div>
                        <x-input-label :for="'deadline_'.$index.'_ends'" value="Fim" />
                        <x-text-input :id="'deadline_'.$index.'_ends'" :name="'deadlines['.$index.'][ends_at]'" type="datetime-local" class="mt-1 block w-full" :value="$deadline['ends_at'] ?? ''" />
                    </div>
                    <div class="lg:col-span-2">
                        <textarea name="deadlines[{{ $index }}][description]" rows="2" class="mv-input block w-full" placeholder="Descrição opcional">{{ $deadline['description'] ?? '' }}</textarea>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>

<section class="mt-8 border-t border-ink-100 pt-6">
    <h2 class="text-lg font-semibold text-ink-900">Júri</h2>
    <p class="mt-1 text-sm text-ink-500">Apenas utilizadores com role de júri são apresentados.</p>

    <div class="mt-5 grid gap-4 lg:grid-cols-2">
        @foreach ($juryRows as $index => $member)
            <div class="rounded-2xl border border-ink-100 bg-ink-50 p-4">
                <x-input-label :for="'jury_'.$index.'_user'" :value="'Membro '.($index + 1)" />
                <select id="jury_{{ $index }}_user" name="jury_members[{{ $index }}][user_id]" class="mv-input mt-1 block w-full">
                    <option value="">Selecionar utilizador</option>
                    @foreach ($juryUsers as $juryUser)
                        <option value="{{ $juryUser->id }}" @selected(($member['user_id'] ?? null) == $juryUser->id)>{{ $juryUser->name }} · {{ $juryUser->email }}</option>
                    @endforeach
                </select>
                <x-text-input name="jury_members[{{ $index }}][role_in_jury]" class="mt-3 block w-full" :value="$member['role_in_jury'] ?? ''" placeholder="Ex.: Presidente, Vogal" />
            </div>
        @endforeach
    </div>
</section>

<div class="mt-8 flex justify-end gap-3">
    <a href="{{ route('admin.contests.index') }}" class="mv-button-secondary">Cancelar</a>
    <x-primary-button>{{ $submitLabel }}</x-primary-button>
</div>
