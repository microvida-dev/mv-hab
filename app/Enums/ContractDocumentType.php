<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ContractDocumentType: string
{
    use HasOptions;

    case ContractHtml = 'contract_html';
    case ContractPdf = 'contract_pdf';
    case Annex = 'annex';
    case SignaturePage = 'signature_page';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ContractHtml => 'Contrato HTML',
            self::ContractPdf => 'Contrato PDF',
            self::Annex => 'Anexo',
            self::SignaturePage => 'Página de assinatura',
            self::Other => 'Outro',
        };
    }
}
