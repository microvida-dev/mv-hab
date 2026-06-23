<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DocumentAiDocumentType: string
{
    use HasOptions;

    case CartaoCidadao = 'cartao_cidadao';
    case TituloResidencia = 'titulo_residencia';
    case Passaporte = 'passaporte';
    case Irs = 'irs';
    case NotaLiquidacao = 'nota_liquidacao';
    case ReciboVencimento = 'recibo_vencimento';
    case DeclaracaoSegurancaSocial = 'declaracao_seguranca_social';
    case DeclaracaoAt = 'declaracao_at';
    case Iban = 'iban';
    case ContratoArrendamento = 'contrato_arrendamento';
    case ComprovativoMorada = 'comprovativo_morada';
    case AtestadoMultiusos = 'atestado_multiusos';
    case CertidaoEscolar = 'certidao_escolar';
    case Outro = 'outro';

    public function label(): string
    {
        return match ($this) {
            self::CartaoCidadao => 'Cartão de Cidadão',
            self::TituloResidencia => 'Título de Residência',
            self::Passaporte => 'Passaporte',
            self::Irs => 'IRS',
            self::NotaLiquidacao => 'Nota de Liquidação',
            self::ReciboVencimento => 'Recibo de vencimento',
            self::DeclaracaoSegurancaSocial => 'Declaração Segurança Social',
            self::DeclaracaoAt => 'Declaração AT',
            self::Iban => 'IBAN',
            self::ContratoArrendamento => 'Contrato de arrendamento',
            self::ComprovativoMorada => 'Comprovativo de morada',
            self::AtestadoMultiusos => 'Atestado Multiusos',
            self::CertidaoEscolar => 'Certidão escolar',
            self::Outro => 'Outro',
        };
    }
}
