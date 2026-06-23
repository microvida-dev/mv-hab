<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AnonymizationMode: string
{
    use HasOptions;

    case None = 'none';
    case PartialName = 'partial_name';
    case ApplicationNumberOnly = 'application_number_only';
    case PublicIdentifierOnly = 'public_identifier_only';
    case MaskedApplicationNumber = 'masked_application_number';
    case FullyAnonymized = 'fully_anonymized';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Sem anonimização',
            self::PartialName => 'Nome parcial',
            self::ApplicationNumberOnly => 'Apenas número de candidatura',
            self::PublicIdentifierOnly => 'Apenas identificador público',
            self::MaskedApplicationNumber => 'Número de candidatura mascarado',
            self::FullyAnonymized => 'Totalmente anonimizado',
        };
    }
}
