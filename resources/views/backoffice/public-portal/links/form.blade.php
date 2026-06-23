<label><span class="text-sm font-semibold text-ink-700">Etiqueta</span><input name="label" value="{{ old('label', $link->label) }}" required class="mt-1 w-full rounded-md border-ink-200 text-sm"></label>
<label><span class="text-sm font-semibold text-ink-700">URL</span><input type="url" name="url" value="{{ old('url', $link->url) }}" required class="mt-1 w-full rounded-md border-ink-200 text-sm"></label>
<label><span class="text-sm font-semibold text-ink-700">Categoria</span><input name="category" value="{{ old('category', $link->category ?: 'institutional') }}" required class="mt-1 w-full rounded-md border-ink-200 text-sm"></label>
<label><span class="text-sm font-semibold text-ink-700">Descrição</span><textarea name="description" rows="3" class="mt-1 w-full rounded-md border-ink-200 text-sm">{{ old('description', $link->description) }}</textarea></label>
<label><span class="text-sm font-semibold text-ink-700">Ordenação</span><input type="number" min="0" name="sort_order" value="{{ old('sort_order', $link->sort_order ?? 0) }}" class="mt-1 w-full rounded-md border-ink-200 text-sm"></label>
<div class="flex flex-wrap gap-5">
    <label class="inline-flex items-center gap-2 text-sm font-semibold text-ink-700"><input type="checkbox" name="opens_new_tab" value="1" @checked(old('opens_new_tab', $link->opens_new_tab ?? true)) class="rounded border-ink-300"> Abrir em novo separador</label>
    <label class="inline-flex items-center gap-2 text-sm font-semibold text-ink-700"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $link->is_active ?? true)) class="rounded border-ink-300"> Ativa</label>
</div>
