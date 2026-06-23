<?php

namespace App\Services\PublicPortal;

use App\Models\Contest;
use App\Models\HousingUnit;
use Illuminate\Support\Facades\Route;

class PublicPortalSeoService
{
    /**
     * @return array<string, string>
     */
    public function offerIndex(): array
    {
        return [
            'title' => 'Oferta Habitacional',
            'description' => 'Consulte a oferta habitacional municipal, concursos publicados, tipologias, rendas e localização pública das habitações disponíveis.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function contest(Contest $contest): array
    {
        return [
            'title' => $contest->title,
            'description' => $contest->summary ?: 'Concurso municipal de Arrendamento Acessível publicado na plataforma MV HAB.',
            'canonical' => route('public.contests.show', $contest->slug),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function housingUnit(HousingUnit $housingUnit): array
    {
        return [
            'title' => $housingUnit->seo_title ?: $housingUnit->displayTitle(),
            'description' => $housingUnit->seo_description ?: ($housingUnit->public_summary ?: 'Ficha pública de habitação municipal.'),
            'canonical' => route('public.housing-units.show', $housingUnit->public_slug),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function housingUnitJsonLd(HousingUnit $housingUnit): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Accommodation',
            'name' => $housingUnit->displayTitle(),
            'description' => $housingUnit->public_summary,
            'url' => Route::has('public.housing-units.show') ? route('public.housing-units.show', $housingUnit->public_slug) : null,
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
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function contestJsonLd(Contest $contest): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'GovernmentService',
            'name' => $contest->title,
            'description' => $contest->summary,
            'provider' => [
                '@type' => 'GovernmentOrganization',
                'name' => data_get($contest, 'program.municipality.name', 'Município'),
            ],
            'url' => route('public.contests.show', $contest->slug),
        ];
    }
}
