@props(['housingUnit'])

@php
    $cover = $housingUnit->coverImage;
    $imageUrl = $cover ? \Illuminate\Support\Facades\Storage::disk($cover->disk)->url($cover->path) : null;
@endphp

<article class="mv-card-interactive h-full overflow-hidden">
    <a href="{{ route('public.housing-units.show', $housingUnit->public_slug) }}" class="block">
        @if ($imageUrl)
            <img
                src="{{ $imageUrl }}"
                alt="{{ $cover->alt_text ?: $housingUnit->displayTitle() }}"
                class="h-56 w-full object-cover"
            >
        @else
            <div class="relative flex h-56 w-full items-center justify-center overflow-hidden bg-gradient-to-br from-mvhab-surface via-white to-sky-50">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(14,165,233,0.14),transparent_18rem)]"></div>

                <div class="relative flex flex-col items-center gap-3">
                    <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-white shadow-surface">
                        <x-mv-icon name="housing" size="xl" class="text-mvhab-primary" />
                    </div>

                    <span class="text-xs font-semibold uppercase tracking-wide text-ink-400">
                        Imagem a publicar
                    </span>
                </div>
            </div>
        @endif
    </a>

    <div class="flex h-full flex-col p-6">
        <div class="flex flex-wrap items-center gap-2">
            <span class="mv-badge mv-badge-civic">
                {{ $housingUnit->typology ?? 'Tipologia a confirmar' }}
            </span>

            <span class="mv-badge mv-badge-neutral">
                {{ $housingUnit->public_status?->label() ?? 'Estado público' }}
            </span>
        </div>

        <h3 class="mv-card-title mt-5">
            <a href="{{ route('public.housing-units.show', $housingUnit->public_slug) }}" class="hover:text-mvhab-primary">
                {{ $housingUnit->displayTitle() }}
            </a>
        </h3>

        <p class="mv-section-description mt-3">
            {{ $housingUnit->public_summary ?: 'Ficha pública de habitação municipal.' }}
        </p>

        <dl class="mt-6 grid grid-cols-2 gap-4 border-t border-ink-100 pt-5">
            <div class="flex gap-3">
                <x-mv-icon name="location" size="sm" class="mt-1 text-mvhab-primary" />

                <div>
                    <dt class="mv-data-label">Localização</dt>
                    <dd class="mv-data-value">
                        {{ $housingUnit->publicLocationLabel() }}
                    </dd>
                </div>
            </div>

            <div>
                <dt class="mv-data-label">Renda</dt>
                <dd class="mt-1 text-lg font-bold text-mvhab-primary">
                    {{ $housingUnit->monthly_rent ? number_format((float) $housingUnit->monthly_rent, 2, ',', ' ') . ' €' : 'A confirmar' }}
                </dd>
            </div>

            <div>
                <dt class="mv-data-label">Área útil</dt>
                <dd class="mv-data-value">
                    {{ $housingUnit->usable_area_sqm ? number_format((float) $housingUnit->usable_area_sqm, 2, ',', ' ') . ' m²' : 'A confirmar' }}
                </dd>
            </div>

            <div>
                <dt class="mv-data-label">Quartos</dt>
                <dd class="mv-data-value">
                    {{ $housingUnit->bedrooms ?? '-' }}
                </dd>
            </div>
        </dl>

        <a
            href="{{ route('public.housing-units.show', $housingUnit->public_slug) }}"
            class="mv-button-secondary mt-6 justify-center"
        >
            Ver ficha pública
            <x-mv-icon name="arrow-right" size="sm" />
        </a>
    </div>
</article>
