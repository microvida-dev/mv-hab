<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ReportFormat: string
{
    use HasOptions;

    case Html = 'html';
    case Pdf = 'pdf';
    case Csv = 'csv';
    case Xlsx = 'xlsx';
    case Json = 'json';
    case Xml = 'xml';
    case Zip = 'zip';

    public function label(): string
    {
        return strtoupper($this->value);
    }

    public function storageExtension(): string
    {
        return match ($this) {
            self::Pdf => 'html',
            self::Xlsx => 'csv',
            default => $this->value,
        };
    }

    /** @return list<self> */
    public static function legacyCases(): array
    {
        return [
            self::Html,
            self::Pdf,
            self::Csv,
            self::Xlsx,
            self::Json,
        ];
    }

    /** @return list<string> */
    public static function legacyValues(): array
    {
        return array_map(
            static fn (self $format): string => $format->value,
            self::legacyCases(),
        );
    }

    /** @return array<string, string> */
    public static function legacyOptions(): array
    {
        $options = [];
        foreach (self::legacyCases() as $format) {
            $options[$format->value] = $format->label();
        }

        return $options;
    }
}
