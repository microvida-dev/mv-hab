<?php

namespace App\Services\Reporting;

use App\Enums\ExportScope;

class SensitiveDataMaskingService
{
    private const PERSONAL_KEYS = ['name', 'email', 'phone', 'address', 'nif', 'tax_number'];

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function maskRows(array $rows, ExportScope $scope): array
    {
        if ($scope === ExportScope::Full) {
            return $rows;
        }

        return array_map(function (array $row) use ($scope) {
            foreach ($row as $key => $value) {
                if (in_array(strtolower((string) $key), self::PERSONAL_KEYS, true)) {
                    if ($scope === ExportScope::Aggregated) {
                        unset($row[$key]);
                    } else {
                        $row[$key] = $this->mask((string) $value);
                    }
                }
            }

            return $row;
        }, $rows);
    }

    private function mask(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (str_contains($value, '@')) {
            [$local, $domain] = explode('@', $value, 2);

            return mb_substr($local, 0, 1).'***@'.$domain;
        }

        return mb_substr($value, 0, 2).str_repeat('*', max(3, mb_strlen($value) - 2));
    }
}
