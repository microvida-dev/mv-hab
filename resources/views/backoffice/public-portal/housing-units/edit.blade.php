<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-civic-700">Portal público</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">Ficha pública da habitação {{ $housingUnit->code }}</h1>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('backoffice.public-portal.housing-units.preview', $housingUnit) }}" class="mv-button-secondary">Pré-visualizar</a>
                <form method="POST" action="{{ route('backoffice.public-portal.housing-units.publish', $housingUnit) }}">
                    @csrf
                    <button class="mv-button-primary">Publicar</button>
                </form>
                <form method="POST" action="{{ route('backoffice.public-portal.housing-units.unpublish', $housingUnit) }}">
                    @csrf
                    <button class="mv-button-secondary">Ocultar</button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-8 px-4 sm:px-6 lg:px-8">
            <x-flash-message />

            <form method="POST" action="{{ route('backoffice.public-portal.housing-units.update', $housingUnit) }}" class="rounded-md border border-ink-100 bg-white p-6">
                @csrf
                @method('PUT')

                <div class="grid gap-5 md:grid-cols-2">
                    <label>
                        <span class="text-sm font-semibold text-ink-700">Município</span>
                        <select name="municipality_id" class="mt-1 w-full rounded-md border-ink-200 text-sm">
                            <option value="">Sem município</option>
                            @foreach ($municipalities as $municipality)
                                <option value="{{ $municipality->id }}" @selected((int) old('municipality_id', $housingUnit->municipality_id) === $municipality->id)>{{ $municipality->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span class="text-sm font-semibold text-ink-700">Referência pública</span>
                        <input name="public_reference" value="{{ old('public_reference', $housingUnit->public_reference) }}" class="mt-1 w-full rounded-md border-ink-200 text-sm">
                    </label>
                    <label>
                        <span class="text-sm font-semibold text-ink-700">Título público</span>
                        <input name="public_title" value="{{ old('public_title', $housingUnit->public_title ?: $housingUnit->displayTitle()) }}" required class="mt-1 w-full rounded-md border-ink-200 text-sm">
                    </label>
                    <label>
                        <span class="text-sm font-semibold text-ink-700">Slug público</span>
                        <input name="public_slug" value="{{ old('public_slug', $housingUnit->public_slug) }}" class="mt-1 w-full rounded-md border-ink-200 text-sm">
                    </label>
                    <label class="md:col-span-2">
                        <span class="text-sm font-semibold text-ink-700">Resumo</span>
                        <textarea name="public_summary" rows="2" class="mt-1 w-full rounded-md border-ink-200 text-sm">{{ old('public_summary', $housingUnit->public_summary) }}</textarea>
                    </label>
                    <label class="md:col-span-2">
                        <span class="text-sm font-semibold text-ink-700">Descrição pública</span>
                        <textarea name="public_description" rows="5" class="mt-1 w-full rounded-md border-ink-200 text-sm">{{ old('public_description', $housingUnit->public_description) }}</textarea>
                    </label>
                    <label>
                        <span class="text-sm font-semibold text-ink-700">Freguesia</span>
                        <input name="parish" value="{{ old('parish', $housingUnit->parish) }}" class="mt-1 w-full rounded-md border-ink-200 text-sm">
                    </label>
                    <label>
                        <span class="text-sm font-semibold text-ink-700">Localidade</span>
                        <input name="locality" value="{{ old('locality', $housingUnit->locality) }}" class="mt-1 w-full rounded-md border-ink-200 text-sm">
                    </label>
                    <label>
                        <span class="text-sm font-semibold text-ink-700">Código postal</span>
                        <input name="postal_code" value="{{ old('postal_code', $housingUnit->postal_code) }}" class="mt-1 w-full rounded-md border-ink-200 text-sm">
                    </label>
                    <label>
                        <span class="text-sm font-semibold text-ink-700">Piso</span>
                        <input name="floor" value="{{ old('floor', $housingUnit->floor) }}" class="mt-1 w-full rounded-md border-ink-200 text-sm">
                    </label>
                    <label>
                        <span class="text-sm font-semibold text-ink-700">Área bruta m²</span>
                        <input type="number" step="0.01" min="0" name="gross_area_sqm" value="{{ old('gross_area_sqm', $housingUnit->gross_area_sqm) }}" class="mt-1 w-full rounded-md border-ink-200 text-sm">
                    </label>
                    <label>
                        <span class="text-sm font-semibold text-ink-700">Área útil m²</span>
                        <input type="number" step="0.01" min="0" name="usable_area_sqm" value="{{ old('usable_area_sqm', $housingUnit->usable_area_sqm) }}" class="mt-1 w-full rounded-md border-ink-200 text-sm">
                    </label>
                    <label>
                        <span class="text-sm font-semibold text-ink-700">Eficiência energética</span>
                        <input name="energy_rating" value="{{ old('energy_rating', $housingUnit->energy_rating) }}" class="mt-1 w-full rounded-md border-ink-200 text-sm">
                    </label>
                    <label>
                        <span class="text-sm font-semibold text-ink-700">Localização pública</span>
                        <input name="public_location_description" value="{{ old('public_location_description', $housingUnit->public_location_description) }}" class="mt-1 w-full rounded-md border-ink-200 text-sm">
                    </label>
                    <label>
                        <span class="text-sm font-semibold text-ink-700">Latitude pública</span>
                        <input type="number" step="0.0000001" name="public_latitude" value="{{ old('public_latitude', $housingUnit->public_latitude) }}" class="mt-1 w-full rounded-md border-ink-200 text-sm">
                    </label>
                    <label>
                        <span class="text-sm font-semibold text-ink-700">Longitude pública</span>
                        <input type="number" step="0.0000001" name="public_longitude" value="{{ old('public_longitude', $housingUnit->public_longitude) }}" class="mt-1 w-full rounded-md border-ink-200 text-sm">
                    </label>
                    <label>
                        <span class="text-sm font-semibold text-ink-700">Precisão da localização</span>
                        <select name="public_location_precision" class="mt-1 w-full rounded-md border-ink-200 text-sm">
                            @foreach ($locationPrecisions as $value => $label)
                                <option value="{{ $value }}" @selected(old('public_location_precision', $housingUnit->public_location_precision?->value ?? 'parish') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span class="text-sm font-semibold text-ink-700">Estado público</span>
                        <select name="public_status" class="mt-1 w-full rounded-md border-ink-200 text-sm">
                            @foreach ($publicStatuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('public_status', $housingUnit->public_status?->value ?? 'available') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span class="text-sm font-semibold text-ink-700">Visibilidade</span>
                        <select name="public_visibility_status" class="mt-1 w-full rounded-md border-ink-200 text-sm">
                            @foreach ($visibilityStatuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('public_visibility_status', $housingUnit->public_visibility_status?->value ?? 'draft') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span class="text-sm font-semibold text-ink-700">Ordenação pública</span>
                        <input type="number" min="0" name="public_sort_order" value="{{ old('public_sort_order', $housingUnit->public_sort_order) }}" class="mt-1 w-full rounded-md border-ink-200 text-sm">
                    </label>
                    <label class="md:col-span-2">
                        <span class="text-sm font-semibold text-ink-700">Título SEO</span>
                        <input name="seo_title" value="{{ old('seo_title', $housingUnit->seo_title) }}" class="mt-1 w-full rounded-md border-ink-200 text-sm">
                    </label>
                    <label class="md:col-span-2">
                        <span class="text-sm font-semibold text-ink-700">Descrição SEO</span>
                        <input name="seo_description" value="{{ old('seo_description', $housingUnit->seo_description) }}" class="mt-1 w-full rounded-md border-ink-200 text-sm">
                    </label>
                </div>

                <div class="mt-5 flex flex-wrap gap-5">
                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-ink-700">
                        <input type="checkbox" name="public_address_visible" value="1" @checked(old('public_address_visible', $housingUnit->public_address_visible)) class="rounded border-ink-300">
                        Mostrar morada completa
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-ink-700">
                        <input type="checkbox" name="is_public" value="1" @checked(old('is_public', $housingUnit->is_public)) class="rounded border-ink-300">
                        Publicável
                    </label>
                </div>

                <div class="mt-6">
                    <button class="mv-button-primary">Guardar ficha pública</button>
                </div>
            </form>

            <div class="grid gap-8 lg:grid-cols-2">
                <section class="rounded-md border border-ink-100 bg-white p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Imagens públicas</h2>
                    <form method="POST" action="{{ route('backoffice.public-portal.housing-units.images.store', $housingUnit) }}" enctype="multipart/form-data" class="mt-5 grid gap-4">
                        @csrf
                        <input type="file" name="image" required accept="image/*" class="text-sm">
                        <input name="title" class="rounded-md border-ink-200 text-sm" placeholder="Título">
                        <input name="alt_text" class="rounded-md border-ink-200 text-sm" placeholder="Texto alternativo">
                        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_cover" value="1" class="rounded border-ink-300"> Capa</label>
                        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_public" value="1" class="rounded border-ink-300"> Pública</label>
                        <button class="mv-button-secondary">Adicionar imagem</button>
                    </form>

                    <div class="mt-6 divide-y divide-ink-100">
                        @forelse ($housingUnit->images as $image)
                            <div class="py-4 text-sm">
                                <form method="POST" action="{{ route('backoffice.public-portal.images.update', $image) }}" class="grid gap-3">
                                    @csrf
                                    @method('PUT')
                                    <input name="title" value="{{ $image->title }}" class="rounded-md border-ink-200 text-sm" placeholder="Título">
                                    <input name="alt_text" value="{{ $image->alt_text }}" class="rounded-md border-ink-200 text-sm" placeholder="Texto alternativo">
                                    <div class="flex flex-wrap gap-4">
                                        <label class="inline-flex items-center gap-2"><input type="checkbox" name="is_cover" value="1" @checked($image->is_cover) class="rounded border-ink-300"> Capa</label>
                                        <label class="inline-flex items-center gap-2"><input type="checkbox" name="is_public" value="1" @checked($image->is_public) class="rounded border-ink-300"> Pública</label>
                                    </div>
                                    <button class="mv-button-secondary">Atualizar imagem</button>
                                </form>
                            </div>
                        @empty
                            <p class="mt-5 text-sm text-ink-500">Sem imagens registadas.</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-md border border-ink-100 bg-white p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Documentos públicos</h2>
                    <form method="POST" action="{{ route('backoffice.public-portal.housing-units.documents.store', $housingUnit) }}" enctype="multipart/form-data" class="mt-5 grid gap-4">
                        @csrf
                        <input type="file" name="document" required accept="application/pdf" class="text-sm">
                        <input name="title" required class="rounded-md border-ink-200 text-sm" placeholder="Título">
                        <select name="document_type" class="rounded-md border-ink-200 text-sm">
                            @foreach ($documentTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <select name="contest_id" class="rounded-md border-ink-200 text-sm">
                            <option value="">Sem concurso específico</option>
                            @foreach ($contests as $contest)
                                <option value="{{ $contest->id }}">{{ $contest->title }}</option>
                            @endforeach
                        </select>
                        <textarea name="description" rows="2" class="rounded-md border-ink-200 text-sm" placeholder="Descrição"></textarea>
                        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_public" value="1" class="rounded border-ink-300"> Público</label>
                        <button class="mv-button-secondary">Adicionar documento</button>
                    </form>

                    <div class="mt-6 divide-y divide-ink-100">
                        @forelse ($housingUnit->publicDocumentRecords as $document)
                            <form method="POST" action="{{ route('backoffice.public-portal.documents.update', $document) }}" class="grid gap-3 py-4 text-sm">
                                @csrf
                                @method('PUT')
                                <input name="title" value="{{ $document->title }}" class="rounded-md border-ink-200 text-sm">
                                <select name="document_type" class="rounded-md border-ink-200 text-sm">
                                    @foreach ($documentTypes as $value => $label)
                                        <option value="{{ $value }}" @selected($document->document_type?->value === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <label class="inline-flex items-center gap-2"><input type="checkbox" name="is_public" value="1" @checked($document->is_public) class="rounded border-ink-300"> Público</label>
                                <button class="mv-button-secondary">Atualizar documento</button>
                            </form>
                        @empty
                            <p class="mt-5 text-sm text-ink-500">Sem documentos públicos registados.</p>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
