<x-public-layout
    :title="$seo['title'] ?? $housingUnit->displayTitle()"
    :description="$seo['description'] ?? ($housingUnit->public_summary ?: 'Ficha pública de habitação municipal.')"
    :canonical="$seo['canonical'] ?? null"
    :og-image="$ogImage ?? ($seo['og_image'] ?? null)"
    :og-type="$seo['og_type'] ?? 'article'"
    :json-ld="$jsonLd ?? null"
>
    @php
        $cover = $housingUnit->coverImage ?: $housingUnit->publicImages->first();
        $coverUrl = $cover ? \Illuminate\Support\Facades\Storage::disk($cover->disk)->url($cover->path) : null;
        $coordinateDecimals = match ($housingUnit->public_location_precision) {
            \App\Enums\HousingLocationPrecision::Exact => 6,
            \App\Enums\HousingLocationPrecision::Street => 4,
            default => 3,
        };
    @endphp

    <section class="mv-section border-b border-ink-100 bg-white">
        <div class="mv-container grid gap-8 lg:grid-cols-[minmax(0,1fr)_28rem] lg:items-center">
            <div>
                <nav aria-label="Breadcrumb" class="text-sm font-semibold text-mvhab-primary">
                    <a href="{{ route('public.portal') }}" class="hover:text-mvhab-primary">Início</a>
                    <span aria-hidden="true" class="mx-2 text-ink-400">/</span>
                    <a href="{{ route('public.housing-offer.index') }}" class="hover:text-mvhab-primary">Oferta habitacional</a>
                    <span aria-hidden="true" class="mx-2 text-ink-400">/</span>
                    <span>{{ $housingUnit->displayTitle() }}</span>
                </nav>

                <div class="mt-6 flex flex-wrap items-center gap-2">
                    <span class="mv-badge mv-badge-neutral">{{ $housingUnit->public_reference ?: $housingUnit->code }}</span>
                    <span class="mv-badge mv-badge-civic">{{ $housingUnit->public_status?->label() }}</span>
                    <span class="mv-badge mv-badge-info">{{ $housingUnit->typology }}</span>
                </div>

                <h1 class="mv-heading mt-5">
                    {{ $housingUnit->displayTitle() }}
                </h1>

                <p class="mv-description mt-6 text-lg">
                    {{ $housingUnit->public_summary ?: 'Ficha pública de habitação municipal.' }}
                </p>
            </div>

            <div class="mv-card overflow-hidden">
                @if ($coverUrl)
                    <img
                        src="{{ $coverUrl }}"
                        alt="{{ $cover->alt_text ?: $housingUnit->displayTitle() }}"
                        class="h-80 w-full object-cover"
                    >
                @else
                    <div class="relative flex h-80 w-full items-center justify-center overflow-hidden bg-gradient-to-br from-mvhab-surface via-white to-sky-50">
                        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(14,165,233,0.14),transparent_18rem)]"></div>

                        <div class="relative flex flex-col items-center gap-3">
                            <div class="flex h-20 w-20 items-center justify-center rounded-3xl bg-white shadow-surface">
                                <x-mv-icon name="housing" size="xl" class="text-mvhab-primary" />
                            </div>

                            <span class="text-xs font-semibold uppercase tracking-wide text-ink-400">
                                Imagem a publicar
                            </span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <div class="mv-container grid gap-8 py-12 lg:grid-cols-[minmax(0,1fr)_24rem]">
        <div class="space-y-12">
            <section class="mv-card p-6">
                <h2 class="mv-card-title">Descrição</h2>
                <div class="mv-description mt-4 whitespace-pre-line">
                    {{ $housingUnit->public_description ?: $housingUnit->public_summary }}
                </div>
            </section>

            <section>
                <h2 class="mv-section-title text-xl">Características públicas</h2>

                <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="mv-card p-5">
                        <dt class="mv-data-label">Localização</dt>
                        <dd class="mv-data-value">{{ $housingUnit->publicLocationLabel() }}</dd>
                    </div>

                    <div class="mv-card p-5">
                        <dt class="mv-data-label">Renda mensal</dt>
                        <dd class="mt-1 text-lg font-bold text-mvhab-primary">
                            {{ $housingUnit->monthly_rent ? number_format((float) $housingUnit->monthly_rent, 2, ',', ' ') . ' €' : 'A confirmar' }}
                        </dd>
                    </div>

                    <div class="mv-card p-5">
                        <dt class="mv-data-label">Área útil</dt>
                        <dd class="mv-data-value">{{ $housingUnit->usable_area_sqm ? number_format((float) $housingUnit->usable_area_sqm, 2, ',', ' ') . ' m²' : 'A confirmar' }}</dd>
                    </div>

                    <div class="mv-card p-5">
                        <dt class="mv-data-label">Área bruta</dt>
                        <dd class="mv-data-value">{{ $housingUnit->gross_area_sqm ? number_format((float) $housingUnit->gross_area_sqm, 2, ',', ' ') . ' m²' : 'A confirmar' }}</dd>
                    </div>

                    <div class="mv-card p-5">
                        <dt class="mv-data-label">Eficiência energética</dt>
                        <dd class="mv-data-value">{{ $housingUnit->energy_rating ?: 'A confirmar' }}</dd>
                    </div>
                </dl>

                @php
                    $hiddenFeatureLabels = [
                        'Área bruta',
                        'Área útil',
                        'Renda acessível',
                        'Renda mensal',
                        'Localização pública',
                        'Localização',
                        'Eficiência energética',
                    ];

                    $publicFeatures = $housingUnit->publicFeatures
                        ->reject(fn ($feature) => in_array($feature->label, $hiddenFeatureLabels, true));
                @endphp

                @if ($publicFeatures->isNotEmpty())
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        @foreach ($publicFeatures as $feature)
                            <div class="mv-card-muted p-5">
                                <p class="font-semibold text-ink-900">{{ $feature->label }}</p>
                                <p class="mv-section-description mt-1">
                                    {{ $feature->value ?: 'Disponível' }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            @if ($housingUnit->publicImages->count() > 1)
                <section>
                    <h2 class="mv-section-title text-xl">Galeria</h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        @foreach ($housingUnit->publicImages as $image)
                            <img
                                src="{{ \Illuminate\Support\Facades\Storage::disk($image->disk)->url($image->path) }}"
                                alt="{{ $image->alt_text ?: $housingUnit->displayTitle() }}"
                                class="h-56 w-full rounded-2xl object-cover shadow-surface"
                            >
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($housingUnit->publicDocuments->isNotEmpty())
                <section>
                    <h2 class="mv-section-title text-xl">Documentos públicos</h2>

                    <div class="mv-card mt-5 divide-y divide-ink-100">
                        @foreach ($housingUnit->publicDocuments as $document)
                            <div class="flex flex-wrap items-center justify-between gap-3 p-5">
                                <div>
                                    <p class="font-semibold text-ink-900">{{ $document->title }}</p>
                                    <p class="mv-section-description">
                                        {{ $document->document_type?->label() }}{{ $document->description ? ' · '.$document->description : '' }}
                                    </p>
                                </div>

                                <a href="{{ route('public.housing-documents.download', $document) }}" class="mv-button-secondary">
                                    Descarregar
                                </a>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        <aside class="space-y-6">
            <section class="mv-card p-6">
                <h2 class="mv-card-title">Preparar candidatura</h2>
                <p class="mv-section-description mt-2">
                    Consulte a brochura simples e confirme os prazos do concurso antes de avançar para a área reservada.
                </p>

                <div class="mt-5 grid gap-3">
                    <a href="{{ route('public.housing-units.brochure', $housingUnit->public_slug) }}" class="mv-button-secondary justify-center">
                        Ver brochura
                    </a>

                    <a href="{{ route('public.simulator.show') }}" class="mv-button-secondary justify-center">
                        Simular elegibilidade
                    </a>

                    <a href="{{ route('login') }}" class="mv-button-primary justify-center">
                        Área reservada
                    </a>
                </div>
            </section>

            <section class="mv-card p-6">
                <h2 class="mv-card-title">Localização pública</h2>
                <p class="mv-section-description mt-2">{{ $housingUnit->publicLocationLabel() }}</p>

                @if ($housingUnit->publicAddressForDisplay())
                    <p class="mt-3 text-sm font-semibold text-ink-900">{{ $housingUnit->publicAddressForDisplay() }}</p>
                @else
                    <p class="mt-3 text-xs leading-5 text-ink-500">
                        A morada completa não é apresentada publicamente.
                    </p>
                @endif

                @if ($housingUnit->hasPublicCoordinates())
                    <div class="mt-5 rounded-2xl bg-mvhab-surface p-4 text-sm text-mvhab-primary">
                        Coordenadas públicas {{ $housingUnit->public_location_precision === \App\Enums\HousingLocationPrecision::Exact ? 'de referência' : 'aproximadas' }}:
                        {{ number_format(round((float) $housingUnit->public_latitude, $coordinateDecimals), $coordinateDecimals, ',', ' ') }},
                        {{ number_format(round((float) $housingUnit->public_longitude, $coordinateDecimals), $coordinateDecimals, ',', ' ') }}
                    </div>
                @endif
            </section>

            <section class="mv-card p-6">
                <h2 class="mv-card-title">Concursos associados</h2>

                <div class="mt-5 space-y-3">
                    @forelse ($housingUnit->contestHousingUnits as $contestHousingUnit)
                        @if ($contestHousingUnit->contest)
                            <a href="{{ route('public.contests.show', $contestHousingUnit->contest->slug) }}" class="block rounded-2xl bg-mvhab-surface p-4 text-sm hover:bg-white">
                                <span class="font-semibold text-mvhab-primary">
                                    {{ $contestHousingUnit->contest->title }}
                                </span>
                                <span class="mt-1 block text-ink-500">
                                    {{ $contestHousingUnit->contest->isOpenForApplications() ? 'Candidaturas abertas' : 'Consultar prazos' }}
                                </span>
                            </a>
                        @endif
                    @empty
                        <p class="text-sm leading-6 text-ink-500">
                            Sem concurso público associado neste momento.
                        </p>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>
</x-public-layout>
