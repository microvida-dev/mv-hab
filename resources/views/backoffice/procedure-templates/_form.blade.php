@php($template = $procedureTemplate ?? null)
@php($defaultTemplateContent = "Exmo(a). {{candidate_name}},\n\nTexto da minuta para o procedimento {{process_number}}.\n\nEste documento deve ser revisto pelos serviços municipais.")
<div class="grid gap-5">
    <label class="text-sm font-semibold text-ink-700">Tipo
        <select name="type" class="mt-1 w-full rounded-md border-ink-200">
            @foreach (\App\Enums\ProcedureTemplateType::cases() as $type)
                <option value="{{ $type->value }}" @selected(old('type', $template?->type?->value) === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
    </label>
    <label class="text-sm font-semibold text-ink-700">Nome
        <input name="name" value="{{ old('name', $template?->name) }}" class="mt-1 w-full rounded-md border-ink-200">
    </label>
    <label class="text-sm font-semibold text-ink-700">Descrição
        <textarea name="description" rows="3" class="mt-1 w-full rounded-md border-ink-200">{{ old('description', $template?->description) }}</textarea>
    </label>
    <label class="text-sm font-semibold text-ink-700">Conteúdo
        <textarea name="content" rows="14" class="mt-1 w-full rounded-md border-ink-200 font-mono text-sm">{{ old('content', $template?->content ?? $defaultTemplateContent) }}</textarea>
    </label>
    <p class="text-xs leading-5 text-ink-500">Variáveis comuns: candidate_name, process_number, application_number, contest_title, program_name, generated_at.</p>
</div>
