<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ApplicationReviewPublicationStatus: string
{
    use HasOptions;

    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Published => 'Publicada',
        };
    }
}
