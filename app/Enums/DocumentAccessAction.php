<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DocumentAccessAction: string
{
    use HasOptions;

    case View = 'view';
    case Download = 'download';
    case Preview = 'preview';
    case Upload = 'upload';
    case Replace = 'replace';
    case Validate = 'validate';
    case Reject = 'reject';
    case Delete = 'delete';

    public function label(): string
    {
        return match ($this) {
            self::View => 'Consulta',
            self::Download => 'Download',
            self::Preview => 'Pré-visualização',
            self::Upload => 'Upload',
            self::Replace => 'Substituição',
            self::Validate => 'Validação',
            self::Reject => 'Rejeição',
            self::Delete => 'Cancelamento',
        };
    }
}
