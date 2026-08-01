<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ApplicationResultExportFormat: string
{
    use HasOptions;

    case Csv = 'csv';
    case Json = 'json';
    case Xml = 'xml';
    case Xlsx = 'xlsx';

    public function label(): string
    {
        return strtoupper($this->value);
    }

    public function mediaType(): string
    {
        return match ($this) {
            self::Csv => 'text/csv; charset=UTF-8',
            self::Json => 'application/json',
            self::Xml => 'application/xml',
            self::Xlsx => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        };
    }
}
