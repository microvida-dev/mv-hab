<form method="POST" action="{{ $action }}" class="mv-surface space-y-5 p-6">
    @csrf
    @if ($method !== 'POST') @method($method) @endif
    <div class="grid gap-4 md:grid-cols-2">
        <select name="contextual_faq_category_id" class="mv-input text-sm">
            <option value="">Categoria</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(old('contextual_faq_category_id', $faq?->contextual_faq_category_id) == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <select name="contest_id" class="mv-input text-sm">
            <option value="">Geral</option>
            @foreach ($contests as $contest)
                <option value="{{ $contest->id }}" @selected(old('contest_id', $faq?->contest_id) == $contest->id)>{{ $contest->title }}</option>
            @endforeach
        </select>
    </div>
    <input name="context_key" value="{{ old('context_key', $faq?->context_key) }}" class="mv-input w-full text-sm" placeholder="Contexto" required>
    <input name="question" value="{{ old('question', $faq?->question) }}" class="mv-input w-full text-sm" placeholder="Pergunta" required>
    <textarea name="answer" rows="7" class="mv-input w-full text-sm" placeholder="Resposta" required>{{ old('answer', $faq?->answer) }}</textarea>
    <div class="grid gap-4 md:grid-cols-2">
        <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $faq?->sort_order ?? 0) }}" class="mv-input text-sm" placeholder="Ordem">
        <input type="datetime-local" name="published_at" value="{{ old('published_at', $faq?->published_at?->format('Y-m-d\\TH:i')) }}" class="mv-input text-sm">
    </div>
    <label class="flex items-center gap-2 text-sm text-ink-700"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $faq?->is_active ?? true)) class="mv-checkbox"> Ativa</label>
    <div class="flex justify-end gap-3"><a href="{{ route('backoffice.contextual-faqs.index') }}" class="mv-button-secondary">Cancelar</a><button class="mv-button-primary">Guardar</button></div>
</form>
