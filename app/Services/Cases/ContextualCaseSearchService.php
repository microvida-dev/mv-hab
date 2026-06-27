<?php

namespace App\Services\Cases;

use Illuminate\Support\Collection;

class ContextualCaseSearchService
{
    /**
     * @param  iterable<array<string, mixed>>  $timeline
     * @param  iterable<array<string, mixed>>  $checklist
     * @param  iterable<array<string, mixed>>  $tabs
     * @return Collection<int, array{label: string, type: string}>
     */
    public function search(string $query, iterable $timeline, iterable $checklist, iterable $tabs): Collection
    {
        $needle = str($query)->lower()->trim()->toString();

        if ($needle === '') {
            return collect();
        }

        return collect()
            ->merge($this->matches($needle, $timeline, 'timeline'))
            ->merge($this->matches($needle, $checklist, 'checklist'))
            ->merge($this->matches($needle, $tabs, 'tab'))
            ->take(10)
            ->values();
    }

    /**
     * @param  iterable<array<string, mixed>>  $items
     * @return Collection<int, array{label: string, type: string}>
     */
    private function matches(string $needle, iterable $items, string $type): Collection
    {
        return collect($items)
            ->filter(function (array $item) use ($needle): bool {
                $haystack = str(implode(' ', array_filter([
                    $item['label'] ?? null,
                    $item['title'] ?? null,
                    $item['description'] ?? null,
                ], 'is_string')))->lower()->toString();

                return str_contains($haystack, $needle);
            })
            ->map(fn (array $item): array => [
                'label' => (string) ($item['label'] ?? $item['title'] ?? 'Resultado'),
                'type' => $type,
            ])
            ->values();
    }
}
