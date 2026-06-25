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

    <section class="border-b border-ink-100 bg-ink-50">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:px-6 lg:grid-cols-[minmax(0,1fr)_28rem] lg:px-8">
            <div>
                <nav aria-label="Breadcrumb" class="text-sm font-semibold text-civic-700">
                    <a href="{{ route('public.portal') }}" class="hover:text-civic-900">Início</a>
                    <span aria-hidden="true" class="mx-2 text-ink-400">/</span>
                    <a href="{{ route('public.housing-offer.index') }}" class="hover:text-civic-900">Oferta habitacional</a>
                    <span aria-hidden="true" class="mx-2 text-ink-400">/</span>
                    <span>{{ $housingUnit->displayTitle() }}</span>
                </nav>
                <div class="mt-5 flex flex-wrap items-center gap-3">
                    <span class="rounded-md bg-white px-2.5 py-1 text-xs font-semibold text-ink-700 ring-1 ring-ink-100">{{ $housingUnit->public_reference ?: $housingUnit->code }}</span>
                    <span class="rounded-md bg-civic-50 px-2.5 py-1 text-xs font-semibold text-civic-900">{{ $housingUnit->public_status?->label() }}</span>
                    <span class="rounded-md bg-white px-2.5 py-1 text-xs font-semibold text-ink-700 ring-1 ring-ink-100">{{ $housingUnit->typology }}</span>
                </div>
                <h1 class="mt-4 max-w-4xl text-3xl font-semibold text-ink-900">{{ $housingUnit->displayTitle() }}</h1>
                <p class="mt-4 max-w-3xl text-lg leading-8 text-ink-600">{{ $housingUnit->public_summary ?: 'Ficha pública de habitação municipal.' }}</p>
            </div>

            <div class="overflow-hidden rounded-md border border-ink-100 bg-white">
                @if ($coverUrl)
                    <img src="{{ $coverUrl }}" alt="{{ $cover->alt_text ?: $housingUnit->displayTitle() }}" class="h-72 w-full object-cover">
                @else
                    <div class="flex h-72 w-full items-center justify-center bg-civic-50 text-lg font-semibold text-civic-800">{{ $housingUnit->typology }}</div>
                @endif
            </div>
        </div>
    </section>

    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[minmax(0,1fr)_24rem] lg:px-8">
        <div class="space-y-10">
            <section>
                <h2 class="text-xl font-semibold text-ink-900">Descrição</h2>
                <div class="mt-4 whitespace-pre-line text-base leading-7 text-ink-600">{{ $housingUnit->public_description ?: $housingUnit->public_summary }}</div>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-ink-900">Características públicas</h2>
                <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-md border border-ink-100 bg-white p-4">
                        <dt class="text-sm text-ink-500">Localização</dt>
                        <dd class="mt-1 font-semibold text-ink-900">{{ $housingUnit->publicLocationLabel() }}</dd>
                    </div>
                    <div class="rounded-md border border-ink-100 bg-white p-4">
                        <dt class="text-sm text-ink-500">Renda mensal</dt>
                        <dd class="mt-1 font-semibold text-ink-900">{{ $housingUnit->monthly_rent ? number_format((float) $housingUnit->monthly_rent, 2, ',', ' ') . ' €' : 'A confirmar' }}</dd>
                    </div>
                    <div class="rounded-md border border-ink-100 bg-white p-4">
                        <dt class="text-sm text-ink-500">Área útil</dt>
                        <dd class="mt-1 font-semibold text-ink-900">{{ $housingUnit->usable_area_sqm ? number_format((float) $housingUnit->usable_area_sqm, 2, ',', ' ') . ' m²' : 'A confirmar' }}</dd>
                    </div>
                    <div class="rounded-md border border-ink-100 bg-white p-4">
                        <dt class="text-sm text-ink-500">Área bruta</dt>
                        <dd class="mt-1 font-semibold text-ink-900">{{ $housingUnit->gross_area_sqm ? number_format((float) $housingUnit->gross_area_sqm, 2, ',', ' ') . ' m²' : 'A confirmar' }}</dd>
                    </div>
                    <div class="rounded-md border border-ink-100 bg-white p-4">
                        <dt class="text-sm text-ink-500">Eficiência energética</dt>
                        <dd class="mt-1 font-semibold text-ink-900">{{ $housingUnit->energy_rating ?: 'A confirmar' }}</dd>
                    </div>
                </dl>

                @if ($housingUnit->publicFeatures->isNotEmpty())
                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        @foreach ($housingUnit->publicFeatures as $feature)
                            <div class="rounded-md bg-ink-50 p-4 text-sm">
                                <p class="font-semibold text-ink-900">{{ $feature->label }}</p>
                                <p class="mt-1 text-ink-600">{{ $feature->value ?: 'Disponível' }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            @if ($housingUnit->publicImages->count() > 1)
                <section>
                    <h2 class="text-xl font-semibold text-ink-900">Galeria</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        @foreach ($housingUnit->publicImages as $image)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk($image->disk)->url($image->path) }}" alt="{{ $image->alt_text ?: $housingUnit->displayTitle() }}" class="h-56 w-full rounded-md object-cover">
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($housingUnit->publicDocuments->isNotEmpty())
                <section>
                    <h2 class="text-xl font-semibold text-ink-900">Documentos públicos</h2>
                    <div class="mt-4 divide-y divide-ink-100 rounded-md border border-ink-100 bg-white">
                        @foreach ($housingUnit->publicDocuments as $document)
                            <div class="flex flex-wrap items-center justify-between gap-3 p-4">
                                <div>
                                    <p class="font-semibold text-ink-900">{{ $document->title }}</p>
                                    <p class="mt-1 text-sm text-ink-500">{{ $document->document_type?->label() }}{{ $document->description ? ' · '.$document->description : '' }}</p>
                                </div>
                                <a href="{{ route('public.housing-documents.download', $document) }}" class="mv-button-secondary">Descarregar</a>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        <aside class="space-y-5">
            <section class="rounded-md border border-ink-100 bg-white p-5">
                <h2 class="font-semibold text-ink-900">Preparar candidatura</h2>
                <p class="mt-2 text-sm leading-6 text-ink-600">Consulte a brochura simples e confirme os prazos do concurso antes de avançar para a área reservada.</p>
                <div class="mt-4 grid gap-3">
                    <a href="{{ route('public.housing-units.brochure', $housingUnit->public_slug) }}" class="mv-button-secondary justify-center">Ver brochura</a>
                    <a href="{{ route('public.simulator.show') }}" class="mv-button-secondary justify-center">Simular elegibilidade</a>
                    <a href="{{ route('login') }}" class="mv-button-primary justify-center">Área reservada</a>
                </div>
            </section>

            <section class="rounded-md border border-ink-100 bg-white p-5">
                <h2 class="font-semibold text-ink-900">Localização pública</h2>
                <p class="mt-2 text-sm leading-6 text-ink-600">{{ $housingUnit->publicLocationLabel() }}</p>
                @if ($housingUnit->publicAddressForDisplay())
                    <p class="mt-2 text-sm font-semibold text-ink-900">{{ $housingUnit->publicAddressForDisplay() }}</p>
                @else
                    <p class="mt-2 text-xs leading-5 text-ink-500">A morada completa não é apresentada publicamente.</p>
                @endif
                @if ($housingUnit->hasPublicCoordinates())
                    <div class="mt-4 rounded-md bg-civic-50 p-4 text-sm text-civic-900">
                        Coordenadas públicas {{ $housingUnit->public_location_precision === \App\Enums\HousingLocationPrecision::Exact ? 'de referência' : 'aproximadas' }}:
                        {{ number_format(round((float) $housingUnit->public_latitude, $coordinateDecimals), $coordinateDecimals, ',', ' ') }},
                        {{ number_format(round((float) $housingUnit->public_longitude, $coordinateDecimals), $coordinateDecimals, ',', ' ') }}
                    </div>
                @endif
            </section>

            <section class="rounded-md border border-ink-100 bg-white p-5">
                <h2 class="font-semibold text-ink-900">Concursos associados</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($housingUnit->contestHousingUnits as $contestHousingUnit)
                        @if ($contestHousingUnit->contest)
                            <a href="{{ route('public.contests.show', $contestHousingUnit->contest->slug) }}" class="block rounded-md bg-ink-50 p-3 text-sm">
                                <span class="font-semibold text-civic-800">{{ $contestHousingUnit->contest->title }}</span>
                                <span class="mt-1 block text-ink-500">{{ $contestHousingUnit->contest->isOpenForApplications() ? 'Candidaturas abertas' : 'Consultar prazos' }}</span>
                            </a>
                        @endif
                    @empty
                        <p class="text-sm leading-6 text-ink-500">Sem concurso público associado neste momento.</p>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>
</x-public-layout>
