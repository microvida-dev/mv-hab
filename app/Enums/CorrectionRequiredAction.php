<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum CorrectionRequiredAction: string
{
    use HasOptions;

    case UploadDocument = 'upload_document';
    case ReplaceDocument = 'replace_document';
    case UpdateData = 'update_data';
    case ProvideExplanation = 'provide_explanation';
    case ConfirmInformation = 'confirm_information';
    case ContactServices = 'contact_services';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::UploadDocument => 'Submeter documento',
            self::ReplaceDocument => 'Substituir documento',
            self::UpdateData => 'Atualizar dados',
            self::ProvideExplanation => 'Prestar esclarecimento',
            self::ConfirmInformation => 'Confirmar informação',
            self::ContactServices => 'Contactar serviços',
            self::Other => 'Outra ação',
        };
    }
}
