<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-mvhab-primary">Portal público</p>
                <h1 class="mt-1 text-2xl font-semibold text-ink-900">
                    Ficha pública da habitação {{ $housingUnit->code }}
                </h1>
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

            <form method="POST" action="{{ route('backoffice.public-portal.housing-units.update', $housingUnit) }}" class="mv-surface p-6">
                @csrf
                @method('PUT')

                <div class="grid gap-5 md:grid-cols-2">
                    <x-ui.field for="municipality_id" name="municipality_id" label="Município">
                        <x-ui.select id="municipality_id" name="municipality_id">
                            <option value="">Sem município</option>
                            @foreach ($municipalities as $municipality)
                                <option value="{{ $municipality->id }}" @selected((int) old('municipality_id', $housingUnit->municipality_id) === $municipality->id)>
                                    {{ $municipality->name }}
                                </option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>

                    <x-ui.field for="public_reference" name="public_reference" label="Referência pública">
                        <x-ui.input id="public_reference" name="public_reference" :value="old('public_reference', $housingUnit->public_reference)" />
                    </x-ui.field>

                    <x-ui.field for="public_title" name="public_title" label="Título público" required>
                        <x-ui.input id="public_title" name="public_title" :value="old('public_title', $housingUnit->public_title ?: $housingUnit->displayTitle())" required />
                    </x-ui.field>

                    <x-ui.field for="public_slug" name="public_slug" label="Slug público">
                        <x-ui.input id="public_slug" name="public_slug" :value="old('public_slug', $housingUnit->public_slug)" />
                    </x-ui.field>

                    <x-ui.field for="public_summary" name="public_summary" label="Resumo" class="md:col-span-2">
                        <x-ui.textarea id="public_summary" name="public_summary" rows="2">{{ old('public_summary', $housingUnit->public_summary) }}</x-ui.textarea>
                    </x-ui.field>

                    <x-ui.field for="public_description" name="public_description" label="Descrição pública" class="md:col-span-2">
                        <x-ui.textarea id="public_description" name="public_description" rows="5">{{ old('public_description', $housingUnit->public_description) }}</x-ui.textarea>
                    </x-ui.field>

                    <x-ui.field for="parish" name="parish" label="Freguesia">
                        <x-ui.input id="parish" name="parish" :value="old('parish', $housingUnit->parish)" />
                    </x-ui.field>

                    <x-ui.field for="locality" name="locality" label="Localidade">
                        <x-ui.input id="locality" name="locality" :value="old('locality', $housingUnit->locality)" />
                    </x-ui.field>

                    <x-ui.field for="postal_code" name="postal_code" label="Código postal">
                        <x-ui.input id="postal_code" name="postal_code" :value="old('postal_code', $housingUnit->postal_code)" />
                    </x-ui.field>

                    <x-ui.field for="floor" name="floor" label="Piso">
                        <x-ui.input id="floor" name="floor" :value="old('floor', $housingUnit->floor)" />
                    </x-ui.field>

                    <x-ui.field for="gross_area_sqm" name="gross_area_sqm" label="Área bruta m²">
                        <x-ui.input id="gross_area_sqm" type="number" step="0.01" min="0" name="gross_area_sqm" :value="old('gross_area_sqm', $housingUnit->gross_area_sqm)" />
                    </x-ui.field>

                    <x-ui.field for="usable_area_sqm" name="usable_area_sqm" label="Área útil m²">
                        <x-ui.input id="usable_area_sqm" type="number" step="0.01" min="0" name="usable_area_sqm" :value="old('usable_area_sqm', $housingUnit->usable_area_sqm)" />
                    </x-ui.field>

                    <x-ui.field for="energy_rating" name="energy_rating" label="Eficiência energética">
                        <x-ui.input id="energy_rating" name="energy_rating" :value="old('energy_rating', $housingUnit->energy_rating)" />
                    </x-ui.field>

                    <x-ui.field for="public_location_description" name="public_location_description" label="Localização pública">
                        <x-ui.input id="public_location_description" name="public_location_description" :value="old('public_location_description', $housingUnit->public_location_description)" />
                    </x-ui.field>

                    <x-ui.field for="public_latitude" name="public_latitude" label="Latitude pública">
                        <x-ui.input id="public_latitude" type="number" step="0.0000001" name="public_latitude" :value="old('public_latitude', $housingUnit->public_latitude)" />
                    </x-ui.field>

                    <x-ui.field for="public_longitude" name="public_longitude" label="Longitude pública">
                        <x-ui.input id="public_longitude" type="number" step="0.0000001" name="public_longitude" :value="old('public_longitude', $housingUnit->public_longitude)" />
                    </x-ui.field>

                    <x-ui.field for="public_location_precision" name="public_location_precision" label="Precisão da localização">
                        <x-ui.select id="public_location_precision" name="public_location_precision">
                            @foreach ($locationPrecisions as $value => $label)
                                <option value="{{ $value }}" @selected(old('public_location_precision', $housingUnit->public_location_precision?->value ?? 'parish') === $value)>{{ $label }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>

                    <x-ui.field for="public_status" name="public_status" label="Estado público">
                        <x-ui.select id="public_status" name="public_status">
                            @foreach ($publicStatuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('public_status', $housingUnit->public_status?->value ?? 'available') === $value)>{{ $label }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>

                    <x-ui.field for="public_visibility_status" name="public_visibility_status" label="Visibilidade">
                        <x-ui.select id="public_visibility_status" name="public_visibility_status">
                            @foreach ($visibilityStatuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('public_visibility_status', $housingUnit->public_visibility_status?->value ?? 'draft') === $value)>{{ $label }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>

                    <x-ui.field for="public_sort_order" name="public_sort_order" label="Ordenação pública">
                        <x-ui.input id="public_sort_order" type="number" min="0" name="public_sort_order" :value="old('public_sort_order', $housingUnit->public_sort_order)" />
                    </x-ui.field>

                    <x-ui.field for="seo_title" name="seo_title" label="Título SEO" class="md:col-span-2">
                        <x-ui.input id="seo_title" name="seo_title" :value="old('seo_title', $housingUnit->seo_title)" />
                    </x-ui.field>

                    <x-ui.field for="seo_description" name="seo_description" label="Descrição SEO" class="md:col-span-2">
                        <x-ui.input id="seo_description" name="seo_description" :value="old('seo_description', $housingUnit->seo_description)" />
                    </x-ui.field>
                </div>

                <div class="mt-5 flex flex-wrap gap-5">
                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-ink-700">
                        <x-ui.checkbox name="public_address_visible" value="1" @checked(old('public_address_visible', $housingUnit->public_address_visible)) />
                        Mostrar morada completa
                    </label>

                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-ink-700">
                        <x-ui.checkbox name="is_public" value="1" @checked(old('is_public', $housingUnit->is_public)) />
                        Publicável
                    </label>
                </div>

                <div class="mt-6">
                    <button class="mv-button-primary">Guardar ficha pública</button>
                </div>
            </form>

            <div class="grid gap-8 lg:grid-cols-2">
                <section class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Imagens públicas</h2>

                    <form method="POST" action="{{ route('backoffice.public-portal.housing-units.images.store', $housingUnit) }}" enctype="multipart/form-data" class="mt-5 grid gap-4">
                        @csrf

                        <x-ui.field for="image" name="image" label="Imagem" required>
                            <input id="image" type="file" name="image" required accept="image/*" class="mv-input">
                        </x-ui.field>

                        <x-ui.field for="image_title" name="title" label="Título">
                            <x-ui.input id="image_title" name="title" placeholder="Título" />
                        </x-ui.field>

                        <x-ui.field for="image_alt_text" name="alt_text" label="Texto alternativo">
                            <x-ui.input id="image_alt_text" name="alt_text" placeholder="Texto alternativo" />
                        </x-ui.field>

                        <div class="flex flex-wrap gap-5">
                            <label class="inline-flex items-center gap-2 text-sm text-ink-700">
                                <x-ui.checkbox name="is_cover" value="1" />
                                Capa
                            </label>

                            <label class="inline-flex items-center gap-2 text-sm text-ink-700">
                                <x-ui.checkbox name="is_public" value="1" />
                                Pública
                            </label>
                        </div>

                        <div class="flex justify-end">
                            <button class="mv-button-secondary">Adicionar imagem</button>
                        </div>
                    </form>

                    <div class="mt-6 divide-y divide-ink-100">
                        @forelse ($housingUnit->images as $image)
                            <div class="py-4 text-sm">
                                <form method="POST" action="{{ route('backoffice.public-portal.images.update', $image) }}" class="grid gap-3">
                                    @csrf
                                    @method('PUT')

                                    <x-ui.field for="image_title_{{ $image->id }}" name="title" label="Título">
                                        <x-ui.input id="image_title_{{ $image->id }}" name="title" :value="$image->title" placeholder="Título" />
                                    </x-ui.field>

                                    <x-ui.field for="image_alt_text_{{ $image->id }}" name="alt_text" label="Texto alternativo">
                                        <x-ui.input id="image_alt_text_{{ $image->id }}" name="alt_text" :value="$image->alt_text" placeholder="Texto alternativo" />
                                    </x-ui.field>

                                    <div class="flex flex-wrap gap-5">
                                        <label class="inline-flex items-center gap-2 text-sm text-ink-700">
                                            <x-ui.checkbox name="is_cover" value="1" @checked($image->is_cover) />
                                            Capa
                                        </label>

                                        <label class="inline-flex items-center gap-2 text-sm text-ink-700">
                                            <x-ui.checkbox name="is_public" value="1" @checked($image->is_public) />
                                            Pública
                                        </label>
                                    </div>

                                    <div class="flex justify-end">
                                        <button class="mv-button-secondary">Atualizar imagem</button>
                                    </div>
                                </form>
                            </div>
                        @empty
                            <p class="mt-5 text-sm text-ink-500">Sem imagens registadas.</p>
                        @endforelse
                    </div>
                </section>

                <section class="mv-surface p-6">
                    <h2 class="text-lg font-semibold text-ink-900">Documentos públicos</h2>

                    <form method="POST" action="{{ route('backoffice.public-portal.housing-units.documents.store', $housingUnit) }}" enctype="multipart/form-data" class="mt-5 grid gap-4">
                        @csrf

                        <x-ui.field for="document" name="document" label="Documento PDF" required>
                            <input id="document" type="file" name="document" required accept="application/pdf" class="mv-input">
                        </x-ui.field>

                        <x-ui.field for="document_title" name="title" label="Título" required>
                            <x-ui.input id="document_title" name="title" required placeholder="Título" />
                        </x-ui.field>

                        <x-ui.field for="document_type" name="document_type" label="Tipo de documento">
                            <x-ui.select id="document_type" name="document_type">
                                @foreach ($documentTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>

                        <x-ui.field for="contest_id" name="contest_id" label="Concurso">
                            <x-ui.select id="contest_id" name="contest_id">
                                <option value="">Sem concurso específico</option>
                                @foreach ($contests as $contest)
                                    <option value="{{ $contest->id }}">{{ $contest->title }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>

                        <x-ui.field for="document_description" name="description" label="Descrição">
                            <x-ui.textarea id="document_description" name="description" rows="2" placeholder="Descrição" />
                        </x-ui.field>

                        <label class="inline-flex items-center gap-2 text-sm text-ink-700">
                            <x-ui.checkbox name="is_public" value="1" />
                            Público
                        </label>

                        <div class="flex justify-end">
                            <button class="mv-button-secondary">Adicionar documento</button>
                        </div>
                    </form>

                    <div class="mt-6 divide-y divide-ink-100">
                        @forelse ($housingUnit->publicDocumentRecords as $document)
                            <form method="POST" action="{{ route('backoffice.public-portal.documents.update', $document) }}" class="grid gap-3 py-4 text-sm">
                                @csrf
                                @method('PUT')

                                <x-ui.field for="document_title_{{ $document->id }}" name="title" label="Título">
                                    <x-ui.input id="document_title_{{ $document->id }}" name="title" :value="$document->title" />
                                </x-ui.field>

                                <x-ui.field for="document_type_{{ $document->id }}" name="document_type" label="Tipo de documento">
                                    <x-ui.select id="document_type_{{ $document->id }}" name="document_type">
                                        @foreach ($documentTypes as $value => $label)
                                            <option value="{{ $value }}" @selected($document->document_type?->value === $value)>{{ $label }}</option>
                                        @endforeach
                                    </x-ui.select>
                                </x-ui.field>

                                <label class="inline-flex items-center gap-2 text-sm text-ink-700">
                                    <x-ui.checkbox name="is_public" value="1" @checked($document->is_public) />
                                    Público
                                </label>

                                <div class="flex justify-end">
                                    <button class="mv-button-secondary">Atualizar documento</button>
                                </div>
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
