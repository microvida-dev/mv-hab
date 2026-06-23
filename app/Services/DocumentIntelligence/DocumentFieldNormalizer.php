<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\DocumentExtractionFlag;
use App\Enums\DocumentAiExtractedFieldType;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Throwable;

class DocumentFieldNormalizer
{
    /**
     * @return array{value: string|int|float|bool|null, normalized_value: string|int|float|bool|null, requires_review: bool, flags: list<DocumentExtractionFlag>}
     */
    public function normalize(string $key, DocumentAiExtractedFieldType $type, string|int|float|bool|null $value): array
    {
        if ($value === null || $value === '') {
            return [
                'value' => null,
                'normalized_value' => null,
                'requires_review' => false,
                'flags' => [],
            ];
        }

        return match ($type) {
            DocumentAiExtractedFieldType::Date => $this->normalizeDate($key, (string) $value),
            DocumentAiExtractedFieldType::Money, DocumentAiExtractedFieldType::Decimal => $this->normalizeMoney($key, (string) $value),
            DocumentAiExtractedFieldType::Percentage => $this->normalizePercentage($key, (string) $value),
            DocumentAiExtractedFieldType::Integer => $this->normalizeInteger($key, (string) $value),
            DocumentAiExtractedFieldType::Identifier => $this->normalizeIdentifier($key, (string) $value),
            DocumentAiExtractedFieldType::Address => $this->normalizeAddress((string) $value),
            default => $this->normalizeString((string) $value),
        };
    }

    /**
     * @return array{value: string, normalized_value: string|null, requires_review: bool, flags: list<DocumentExtractionFlag>}
     */
    private function normalizeDate(string $key, string $value): array
    {
        $clean = $this->clean($value);
        $formats = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'd.m.Y'];

        foreach ($formats as $format) {
            try {
                $date = CarbonImmutable::createFromFormat('!'.$format, $clean);

                if ($date instanceof CarbonImmutable && $date->format($format) === $clean) {
                    return [
                        'value' => $clean,
                        'normalized_value' => $date->format('Y-m-d'),
                        'requires_review' => false,
                        'flags' => [],
                    ];
                }
            } catch (Throwable) {
                continue;
            }
        }

        return [
            'value' => $clean,
            'normalized_value' => null,
            'requires_review' => true,
            'flags' => [new DocumentExtractionFlag('ambiguous_date', 'medium', 'Data extraída com formato ambíguo.', $key)],
        ];
    }

    /**
     * @return array{value: string, normalized_value: float|null, requires_review: bool, flags: list<DocumentExtractionFlag>}
     */
    private function normalizeMoney(string $key, string $value): array
    {
        $clean = str_replace(["\u{00A0}", 'EUR', '€'], ' ', $value);
        $clean = trim((string) preg_replace('/\s+/u', ' ', $clean));
        $numeric = str_replace(' ', '', $clean);

        if (str_contains($numeric, ',')) {
            $numeric = str_replace('.', '', $numeric);
            $numeric = str_replace(',', '.', $numeric);
        }

        $numeric = preg_replace('/[^0-9.\-]/', '', (string) $numeric);

        if ($numeric === '' || ! is_numeric($numeric)) {
            return [
                'value' => $clean,
                'normalized_value' => null,
                'requires_review' => true,
                'flags' => [new DocumentExtractionFlag('ambiguous_money_value', 'medium', 'Valor monetário extraído com formato ambíguo.', $key)],
            ];
        }

        $amount = round((float) $numeric, 2);

        return [
            'value' => $clean,
            'normalized_value' => $amount,
            'requires_review' => $amount < 0,
            'flags' => $amount < 0 ? [new DocumentExtractionFlag('negative_money_value', 'medium', 'Valor monetário negativo exige revisão.', $key)] : [],
        ];
    }

    /**
     * @return array{value: string, normalized_value: float|null, requires_review: bool, flags: list<DocumentExtractionFlag>}
     */
    private function normalizePercentage(string $key, string $value): array
    {
        $clean = trim(str_replace('%', '', $value));
        $clean = str_replace(',', '.', $clean);
        $clean = preg_replace('/[^0-9.\-]/', '', (string) $clean);

        if ($clean === '' || ! is_numeric($clean)) {
            return [
                'value' => $value,
                'normalized_value' => null,
                'requires_review' => true,
                'flags' => [new DocumentExtractionFlag('ambiguous_percentage', 'medium', 'Percentagem extraída com formato ambíguo.', $key)],
            ];
        }

        $percentage = round((float) $clean, 2);
        $invalid = $percentage < 0 || $percentage > 100;

        return [
            'value' => $value,
            'normalized_value' => $percentage,
            'requires_review' => $invalid,
            'flags' => $invalid ? [new DocumentExtractionFlag('percentage_out_of_range', 'medium', 'Percentagem fora do intervalo 0-100.', $key)] : [],
        ];
    }

    /**
     * @return array{value: string, normalized_value: int|null, requires_review: bool, flags: list<DocumentExtractionFlag>}
     */
    private function normalizeInteger(string $key, string $value): array
    {
        $clean = preg_replace('/[^0-9\-]/', '', $value);

        if ($clean === '' || ! is_numeric($clean)) {
            return [
                'value' => $value,
                'normalized_value' => null,
                'requires_review' => true,
                'flags' => [new DocumentExtractionFlag('invalid_integer', 'medium', 'Número inteiro inválido.', $key)],
            ];
        }

        return [
            'value' => $value,
            'normalized_value' => (int) $clean,
            'requires_review' => false,
            'flags' => [],
        ];
    }

    /**
     * @return array{value: string, normalized_value: string|null, requires_review: bool, flags: list<DocumentExtractionFlag>}
     */
    private function normalizeIdentifier(string $key, string $value): array
    {
        $clean = strtoupper($this->clean($value));

        if ($key === 'nif') {
            $digits = preg_replace('/\D/', '', $clean);
            $valid = is_string($digits) && preg_match('/^\d{9}$/', $digits) === 1;

            return [
                'value' => $clean,
                'normalized_value' => $valid ? $digits : null,
                'requires_review' => ! $valid,
                'flags' => $valid ? [] : [new DocumentExtractionFlag('invalid_nif_format', 'medium', 'NIF extraído com formato inválido.', $key)],
            ];
        }

        return [
            'value' => $clean,
            'normalized_value' => preg_replace('/\s+/', '', $clean),
            'requires_review' => false,
            'flags' => [],
        ];
    }

    /**
     * @return array{value: string, normalized_value: string, requires_review: bool, flags: list<DocumentExtractionFlag>}
     */
    private function normalizeAddress(string $value): array
    {
        $clean = $this->clean($value);

        return [
            'value' => $clean,
            'normalized_value' => $clean,
            'requires_review' => false,
            'flags' => [],
        ];
    }

    /**
     * @return array{value: string, normalized_value: string, requires_review: bool, flags: list<DocumentExtractionFlag>}
     */
    private function normalizeString(string $value): array
    {
        $clean = $this->clean($value);

        return [
            'value' => $clean,
            'normalized_value' => $clean,
            'requires_review' => false,
            'flags' => [],
        ];
    }

    private function clean(string $value): string
    {
        $value = Str::of($value)
            ->replace(["\n", "\r", "\t"], ' ')
            ->trim()
            ->toString();

        return trim((string) preg_replace('/\s+/u', ' ', $value), " \t\n\r\0\x0B:;-");
    }
}
