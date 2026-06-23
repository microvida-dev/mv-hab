<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DocumentAiValidationSeverity: string
{
    use HasOptions;

    case None = 'none';
    case Light = 'divergencia_ligeira';
    case Medium = 'divergencia_media';
    case Critical = 'divergencia_critica';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Sem divergência',
            self::Light => 'Divergência ligeira',
            self::Medium => 'Divergência média',
            self::Critical => 'Divergência crítica',
        };
    }
}
