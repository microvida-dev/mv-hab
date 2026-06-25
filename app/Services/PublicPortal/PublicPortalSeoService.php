<?php

namespace App\Services\PublicPortal;

use App\Models\Contest;
use App\Models\HousingUnit;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

class PublicPortalSeoService
{
    /**
     * @return array<string, string|null>
     */
    public function offerIndex(): array
    {
        return [
            'title' => 'Oferta Habitacional',
            'description' => 'Consulte a oferta habitacional municipal, concursos publicados, tipologias, rendas e localização pública das habitações disponíveis.',
            'canonical' => route('public.housing-offer.index'),
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public function contest(Contest $contest): array
    {
        return [
            'title' => $contest->title,
            'description' => $contest->summary ?: 'Concurso municipal de Arrendamento Acessível publicado na plataforma MV HAB.',
            'canonical' => route('public.contests.show', $contest->slug),
            'og_type' => 'article',
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public function housingUnit(HousingUnit $housingUnit): array
    {
        return [
            'title' => $housingUnit->seo_title ?: $housingUnit->displayTitle(),
            'description' => $housingUnit->seo_description ?: ($housingUnit->public_summary ?: 'Ficha pública de habitação municipal.'),
            'canonical' => route('public.housing-units.show', $housingUnit->public_slug),
            'og_type' => 'article',
            'og_image' => $this->housingUnitImageUrl($housingUnit),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function housingUnitJsonLd(HousingUnit $housingUnit): array
    {
        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'RealEstateListing',
                    'name' => $housingUnit->displayTitle(),
                    'description' => $housingUnit->public_summary,
                    'url' => Route::has('public.housing-units.show') ? route('public.housing-units.show', $housingUnit->public_slug) : null,
                    'image' => $this->housingUnitImageUrl($housingUnit),
                    'offers' => $housingUnit->monthly_rent ? [
                        '@type' => 'Offer',
                        'price' => (float) $housingUnit->monthly_rent,
                        'priceCurrency' => 'EUR',
                        'availability' => 'https://schema.org/LimitedAvailability',
                    ] : null,
                    'itemOffered' => [
                        '@type' => 'Apartment',
                        'name' => $housingUnit->displayTitle(),
                        'address' => [
                            '@type' => 'PostalAddress',
                            'addressLocality' => $housingUnit->publicLocationLabel(),
                            'addressCountry' => 'PT',
                        ],
                        'floorSize' => $housingUnit->usable_area_sqm ? [
                            '@type' => 'QuantitativeValue',
                            'value' => (float) $housingUnit->usable_area_sqm,
                            'unitCode' => 'MTK',
                        ] : null,
                    ],
                ],
                $this->breadcrumbs([
                    ['name' => 'Início', 'url' => route('public.portal')],
                    ['name' => 'Oferta habitacional', 'url' => route('public.housing-offer.index')],
                    ['name' => $housingUnit->displayTitle(), 'url' => route('public.housing-units.show', $housingUnit->public_slug)],
                ]),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function contestJsonLd(Contest $contest): array
    {
        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'GovernmentService',
                    'name' => $contest->title,
                    'description' => $contest->summary,
                    'provider' => [
                        '@type' => 'GovernmentOrganization',
                        'name' => data_get($contest, 'program.municipality.name', 'Município'),
                    ],
                    'url' => route('public.contests.show', $contest->slug),
                ],
                $this->breadcrumbs([
                    ['name' => 'Início', 'url' => route('public.portal')],
                    ['name' => 'Concursos', 'url' => route('public.contests.index')],
                    ['name' => $contest->title, 'url' => route('public.contests.show', $contest->slug)],
                ]),
            ],
        ];
    }

    /**
     * @param  list<array{name: string, url: string}>  $items
     * @return array<string, mixed>
     */
    public function breadcrumbs(array $items): array
    {
        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)
                ->values()
                ->map(fn (array $item, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['name'],
                    'item' => $item['url'],
                ])
                ->all(),
        ];
    }

    public function housingUnitImageUrl(HousingUnit $housingUnit): ?string
    {
        $cover = $housingUnit->coverImage ?: $housingUnit->publicImages->first();

        if ($cover !== null && $cover->is_public) {
            return Storage::disk($cover->disk)->url($cover->path);
        }

        return is_string($housingUnit->og_image_path) && $housingUnit->og_image_path !== ''
            ? Storage::disk('public')->url($housingUnit->og_image_path)
            : null;
    }
}
