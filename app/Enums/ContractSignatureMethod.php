<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ContractSignatureMethod: string
{
    use HasOptions;

    case Manual = 'manual';
    case InPerson = 'in_person';
    case UploadedSignedDocument = 'uploaded_signed_document';
    case InternalValidation = 'internal_validation';
    case DigitalPending = 'digital_pending';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::InPerson => 'Presencial',
            self::UploadedSignedDocument => 'Documento assinado carregado',
            self::InternalValidation => 'Validação interna',
            self::DigitalPending => 'Digital pendente',
            self::Other => 'Outro',
        };
    }
}
