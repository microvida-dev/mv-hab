<?php

namespace App\Services\Regulatory\RentLimits;

use App\Support\DecimalMoney;
use JsonException;

final class RentLimitTableChecksumService
{
    /**
     * @param  iterable<int, array<string, mixed>|object>  $rows
     *
     * @throws JsonException
     */
    public function calculate(iterable $rows): string
    {
        $normalized = collect($rows)
            ->map(fn (array|object $row): array => [
                'municipality_code' => strtoupper(trim((string) data_get($row, 'municipality_code'))),
                'typology' => strtoupper(trim((string) data_get($row, 'typology'))),
                'minimum_rent' => data_get($row, 'minimum_rent') === null
                    ? null
                    : DecimalMoney::normalize((string) data_get($row, 'minimum_rent')),
                'maximum_rent' => DecimalMoney::normalize((string) data_get($row, 'maximum_rent')),
                'source_row_reference' => $this->nullableString(
                    data_get($row, 'source_row_reference'),
                ),
            ])
            ->sortBy([
                ['municipality_code', 'asc'],
                ['typology', 'asc'],
            ])
            ->values()
            ->all();

        return hash('sha256', json_encode(
            $normalized,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== ''
            ? trim($value)
            : null;
    }
}
