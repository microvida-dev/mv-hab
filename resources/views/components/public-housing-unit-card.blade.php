@props(['housingUnit'])

@php
    $cover = $housingUnit->coverImage;
    $imageUrl = $cover ? \Illuminate\Support\Facades\Storage::disk($cover->disk)->url($cover->path) : null;
@endphp

<article class="mv-surface overflow-hidden">
    <a href="{{ route('public.housing-units.show', $housingUnit->public_slug) }}" class="block">
        @if ($imageUrl)
            <img src="{{ $imageUrl }}" alt="{{ $cover->alt_text ?: $housingUnit->displayTitle() }}" class="h-48 w-full object-cover">
        @else
            <div class="flex h-48 w-full items-center justify-center bg-mvhab-surface text-sm font-semibold text-mvhab-primary">
                {{ $housingUnit->typology ?? 'Habitação' }}
            </div>
        @endif
    </a>

    <div class="p-5">
        <div class="flex flex-wrap items-center gap-2">
            <span class="rounded-2xl bg-mvhab-surface px-2.5 py-1 text-xs font-semibold text-mvhab-primary">{{ $housingUnit->typology ?? 'Tipologia a confirmar' }}</span>
            <span class="rounded-2xl bg-ink-50 px-2.5 py-1 text-xs font-semibold text-ink-700">{{ $housingUnit->public_status?->label() ?? 'Estado público' }}</span>
        </div>

        <h3 class="mt-3 text-lg font-semibold text-ink-900">
            <a href="{{ route('public.housing-units.show', $housingUnit->public_slug) }}" class="hover:text-mvhab-primary">{{ $housingUnit->displayTitle() }}</a>
        </h3>

        <p class="mt-2 text-sm leading-6 text-ink-500">{{ $housingUnit->public_summary ?: 'Ficha pública de habitação municipal.' }}</p>

        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
            <div>
                <dt class="text-ink-500">Localização</dt>
                <dd class="mt-1 font-semibold text-ink-800">{{ $housingUnit->publicLocationLabel() }}</dd>
            </div>
            <div>
                <dt class="text-ink-500">Renda</dt>
                <dd class="mt-1 font-semibold text-ink-800">{{ $housingUnit->monthly_rent ? number_format((float) $housingUnit->monthly_rent, 2, ',', ' ') . ' €' : 'A confirmar' }}</dd>
            </div>
            <div>
                <dt class="text-ink-500">Área útil</dt>
                <dd class="mt-1 font-semibold text-ink-800">{{ $housingUnit->usable_area_sqm ? number_format((float) $housingUnit->usable_area_sqm, 2, ',', ' ') . ' m²' : 'A confirmar' }}</dd>
            </div>
            <div>
                <dt class="text-ink-500">Quartos</dt>
                <dd class="mt-1 font-semibold text-ink-800">{{ $housingUnit->bedrooms ?? '-' }}</dd>
            </div>
        </dl>

        <a href="{{ route('public.housing-units.show', $housingUnit->public_slug) }}" class="mt-5 inline-flex text-sm font-semibold text-mvhab-primary hover:text-mvhab-primary">Ver ficha pública</a>
    </div>
</article>
