<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum TemplateType: string
{
    use HasOptions;

    case InApp = 'in_app';
    case Internal = 'internal';
    case Email = 'email';
    case Sms = 'sms';
    case Postal = 'postal';
    case Document = 'document';

    public function label(): string
    {
        return CommunicationChannel::from($this->value)->label();
    }
}
