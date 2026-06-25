<?php

namespace App\Services\PublicPortal;

use App\Models\Contest;
use App\Models\HousingUnit;
use App\Models\Program;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class PublicSitemapService
{
    /**
     * @return list<array{loc: string, lastmod: string|null, changefreq: string, priority: string}>
     */
    public function urls(): array
    {
        return [
            ...$this->staticUrls(),
            ...$this->programUrls(),
            ...$this->contestUrls(),
            ...$this->housingUnitUrls(),
        ];
    }

    /**
     * @return list<array{loc: string, lastmod: string|null, changefreq: string, priority: string}>
     */
    private function staticUrls(): array
    {
        return [
            $this->entry(route('public.portal'), null, 'daily', '1.0'),
            $this->entry(route('public.housing-offer.index'), null, 'daily', '0.9'),
            $this->entry(route('public.programs.index'), null, 'weekly', '0.8'),
            $this->entry(route('public.contests.index'), null, 'daily', '0.9'),
            $this->entry(route('public.housing-units.index'), null, 'daily', '0.9'),
            $this->entry(route('public.simulator.show'), null, 'monthly', '0.7'),
            $this->entry(route('public.faq'), null, 'monthly', '0.5'),
        ];
    }

    /**
     * @return list<array{loc: string, lastmod: string|null, changefreq: string, priority: string}>
     */
    private function programUrls(): array
    {
        return array_values(Program::query()
            ->publiclyVisible()
            ->select(['id', 'slug', 'updated_at', 'published_at'])
            ->orderBy('slug')
            ->get()
            ->map(fn (Program $program): array => $this->entry(
                route('public.programs.show', $program->slug),
                $program->updated_at ?? $program->published_at,
                'weekly',
                '0.7',
            ))
            ->values()
            ->all());
    }

    /**
     * @return list<array{loc: string, lastmod: string|null, changefreq: string, priority: string}>
     */
    private function contestUrls(): array
    {
        return array_values(Contest::query()
            ->publiclyVisible()
            ->select(['id', 'slug', 'updated_at', 'published_at'])
            ->orderBy('slug')
            ->get()
            ->map(fn (Contest $contest): array => $this->entry(
                route('public.contests.show', $contest->slug),
                $contest->updated_at ?? $contest->published_at,
                'daily',
                '0.8',
            ))
            ->values()
            ->all());
    }

    /**
     * @return list<array{loc: string, lastmod: string|null, changefreq: string, priority: string}>
     */
    private function housingUnitUrls(): array
    {
        return array_values(HousingUnit::query()
            ->publiclyVisible()
            ->select(['id', 'public_slug', 'updated_at', 'published_at'])
            ->orderBy('public_slug')
            ->get()
            ->map(fn (HousingUnit $housingUnit): array => $this->entry(
                route('public.housing-units.show', $housingUnit->public_slug),
                $housingUnit->updated_at ?? $housingUnit->published_at,
                'weekly',
                '0.8',
            ))
            ->values()
            ->all());
    }

    /**
     * @return array{loc: string, lastmod: string|null, changefreq: string, priority: string}
     */
    private function entry(string $loc, mixed $lastmod, string $changefreq, string $priority): array
    {
        return [
            'loc' => $loc,
            'lastmod' => $this->lastmod($lastmod),
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];
    }

    private function lastmod(mixed $value): ?string
    {
        if ($value instanceof CarbonInterface) {
            return $value->toDateString();
        }

        if (is_string($value) && $value !== '') {
            return Carbon::parse($value)->toDateString();
        }

        return null;
    }
}
