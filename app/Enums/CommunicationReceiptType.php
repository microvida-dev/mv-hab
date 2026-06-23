<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum CommunicationReceiptType: string
{
    use HasOptions;

    case SendProof = 'send_proof';
    case DeliveryProof = 'delivery_proof';
    case ReadProof = 'read_proof';
    case AcknowledgementProof = 'acknowledgement_proof';
    case PostalProof = 'postal_proof';
    case ManualUpload = 'manual_upload';

    public function label(): string
    {
        return match ($this) {
            self::SendProof => 'Comprovativo de envio',
            self::DeliveryProof => 'Comprovativo de entrega',
            self::ReadProof => 'Comprovativo de leitura',
            self::AcknowledgementProof => 'Comprovativo de tomada de conhecimento',
            self::PostalProof => 'Comprovativo postal',
            self::ManualUpload => 'Comprovativo carregado manualmente',
        };
    }
}
