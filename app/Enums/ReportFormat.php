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
}
