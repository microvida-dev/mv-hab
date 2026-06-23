<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DocumentAiExtractionSource: string
{
    use HasOptions;

    case Ocr = 'ocr';
    case Regex = 'regex';
    case Keywords = 'keywords';
    case Layout = 'layout';
    case LocalAi = 'local_ai';
    case Combined = 'combined';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Ocr => 'OCR',
            self::Regex => 'Regex',
            self::Keywords => 'Palavras-chave',
            self::Layout => 'Layout',
            self::LocalAi => 'IA local',
            self::Combined => 'Combinado',
            self::Manual => 'Manual',
        };
    }
}
