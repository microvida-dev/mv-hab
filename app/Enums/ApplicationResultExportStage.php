<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ApplicationResultExportStage: string
{
    use HasOptions;

    case Queued = 'queued';
    case Snapshotting = 'snapshotting';
    case Rendering = 'rendering';
    case Packaging = 'packaging';
    case Completed = 'completed';
    case Failed = 'failed';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'Em fila',
            self::Snapshotting => 'A capturar snapshot',
            self::Rendering => 'A gerar formatos',
            self::Packaging => 'A validar pacote',
            self::Completed => 'Concluída',
            self::Failed => 'Falhou',
            self::Expired => 'Expirada',
        };
    }
}
