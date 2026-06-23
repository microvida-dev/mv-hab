<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\DocumentValidationRule;
use App\Enums\DocumentAiComparisonMethod;
use App\Enums\DocumentAiDocumentType;
use App\Enums\DocumentAiValidationGroup;
use App\Enums\DocumentAiValidationSeverity;

class DocumentValidationRuleRegistry
{
    /**
     * @return list<DocumentValidationRule>
     */
    public function rulesFor(?DocumentAiDocumentType $documentType): array
    {
        if (! $documentType instanceof DocumentAiDocumentType) {
            return [];
        }

        return match ($documentType) {
            DocumentAiDocumentType::CartaoCidadao => [
                $this->identityName('name', 'Nome coincide', 'fields.name'),
                $this->identityNif('nif', 'NIF coincide', 'fields.nif'),
                $this->identityBirthDate('birth_date', 'Data de nascimento coincide', 'fields.birth_date'),
                $this->identityDocumentNumber('document_number', 'Número de documento coincide', 'fields.document_number'),
            ],
            DocumentAiDocumentType::TituloResidencia => [
                $this->identityName('name', 'Nome coincide', 'fields.name'),
                $this->identityDocumentNumber('document_number', 'Número de título coincide', 'fields.document_number'),
                $this->identityNationality('nationality', 'Nacionalidade coincide', 'fields.nationality'),
            ],
            DocumentAiDocumentType::Passaporte => [
                $this->identityName('name', 'Nome coincide', 'fields.name'),
            ],
            DocumentAiDocumentType::Irs => [
                $this->identityName('taxpayer_name', 'Sujeito passivo coincide', 'fields.taxpayer_name'),
                $this->identityNif('irs_nif', 'NIF fiscal coincide', 'fields.nif'),
                $this->incomeAnnual('gross_income', 'IRS vs formulário', 'fields.gross_income'),
            ],
            DocumentAiDocumentType::NotaLiquidacao => [
                $this->incomeAnnual('total_income', 'Nota de liquidação vs formulário', 'fields.total_income'),
            ],
            DocumentAiDocumentType::ReciboVencimento => [
                $this->identityName('worker', 'Trabalhador coincide', 'fields.worker'),
                $this->incomeMonthly('gross_amount', 'Recibo ilíquido vs rendimento mensal', 'fields.gross_amount'),
                $this->incomeMonthly('net_amount', 'Recibo líquido vs rendimento mensal', 'fields.net_amount', DocumentAiValidationSeverity::Medium),
            ],
            DocumentAiDocumentType::DeclaracaoSegurancaSocial => [
                $this->identityName('beneficiary', 'Beneficiário coincide', 'fields.beneficiary'),
                $this->incomeMonthly('amount', 'Prestação vs rendimento mensal', 'fields.amount', DocumentAiValidationSeverity::Medium),
            ],
            DocumentAiDocumentType::ContratoArrendamento => [
                $this->identityName('tenant', 'Inquilino coincide', 'fields.tenant'),
                $this->housingAddress('address', 'Morada habitacional coincide', 'fields.address'),
                $this->housingRent('rent_amount', 'Renda declarada coincide', 'fields.rent_amount'),
            ],
            DocumentAiDocumentType::ComprovativoMorada => [
                $this->housingAddress('address', 'Morada declarada coincide', 'fields.address'),
            ],
            DocumentAiDocumentType::AtestadoMultiusos => [
                new DocumentValidationRule(
                    group: DocumentAiValidationGroup::Household,
                    key: 'disability_degree',
                    label: 'Grau de incapacidade compatível',
                    candidatePath: 'household.max_disability_percentage',
                    extractedPath: 'fields.disability_degree',
                    method: DocumentAiComparisonMethod::DocumentConsistency,
                    valueType: 'percentage',
                    baseSeverity: DocumentAiValidationSeverity::Medium,
                    sensitive: true,
                    healthData: true,
                    message: 'Grau de incapacidade comparado com a declaração do agregado.',
                    recommendation: 'Rever manualmente o atestado multiusos e a informação declarada pelo candidato.',
                ),
            ],
            default => [],
        };
    }

    private function identityName(string $key, string $label, string $extractedPath): DocumentValidationRule
    {
        return new DocumentValidationRule(
            group: DocumentAiValidationGroup::Identification,
            key: $key,
            label: $label,
            candidatePath: 'identity.name',
            extractedPath: $extractedPath,
            method: DocumentAiComparisonMethod::FuzzyName,
            valueType: 'string',
            baseSeverity: DocumentAiValidationSeverity::Critical,
            sensitive: true,
            message: 'Nome extraído comparado com o nome declarado.',
            recommendation: 'Confirmar se o documento pertence ao candidato ou elemento correto do agregado.',
        );
    }

    private function identityNif(string $key, string $label, string $extractedPath): DocumentValidationRule
    {
        return new DocumentValidationRule(
            group: DocumentAiValidationGroup::Identification,
            key: $key,
            label: $label,
            candidatePath: 'identity.nif',
            extractedPath: $extractedPath,
            method: DocumentAiComparisonMethod::NormalizedExact,
            valueType: 'identifier',
            baseSeverity: DocumentAiValidationSeverity::Critical,
            sensitive: true,
            message: 'NIF documental comparado com o NIF declarado.',
            recommendation: 'Solicitar confirmação documental quando o NIF não coincide.',
        );
    }

    private function identityBirthDate(string $key, string $label, string $extractedPath): DocumentValidationRule
    {
        return new DocumentValidationRule(
            group: DocumentAiValidationGroup::Identification,
            key: $key,
            label: $label,
            candidatePath: 'identity.birth_date',
            extractedPath: $extractedPath,
            method: DocumentAiComparisonMethod::Date,
            valueType: 'date',
            baseSeverity: DocumentAiValidationSeverity::Critical,
            sensitive: true,
            message: 'Data de nascimento extraída comparada com a data declarada.',
            recommendation: 'Confirmar a identificação civil antes de qualquer decisão administrativa.',
        );
    }

    private function identityDocumentNumber(string $key, string $label, string $extractedPath): DocumentValidationRule
    {
        return new DocumentValidationRule(
            group: DocumentAiValidationGroup::Identification,
            key: $key,
            label: $label,
            candidatePath: 'identity.document_number',
            extractedPath: $extractedPath,
            method: DocumentAiComparisonMethod::NormalizedExact,
            valueType: 'identifier',
            baseSeverity: DocumentAiValidationSeverity::Medium,
            sensitive: true,
            message: 'Número de documento comparado com a informação declarada.',
            recommendation: 'Confirmar manualmente se o documento submetido está atualizado.',
        );
    }

    private function identityNationality(string $key, string $label, string $extractedPath): DocumentValidationRule
    {
        return new DocumentValidationRule(
            group: DocumentAiValidationGroup::Identification,
            key: $key,
            label: $label,
            candidatePath: 'identity.nationality',
            extractedPath: $extractedPath,
            method: DocumentAiComparisonMethod::NormalizedExact,
            valueType: 'string',
            baseSeverity: DocumentAiValidationSeverity::Medium,
            sensitive: true,
            message: 'Nacionalidade extraída comparada com a nacionalidade declarada.',
            recommendation: 'Confirmar validade do título e situação declarada quando divergente.',
        );
    }

    private function incomeAnnual(string $key, string $label, string $extractedPath): DocumentValidationRule
    {
        return new DocumentValidationRule(
            group: DocumentAiValidationGroup::Income,
            key: $key,
            label: $label,
            candidatePath: 'income.annual_total',
            extractedPath: $extractedPath,
            method: DocumentAiComparisonMethod::MoneyTolerance,
            valueType: 'money',
            baseSeverity: DocumentAiValidationSeverity::Critical,
            sensitive: true,
            income: true,
            message: 'Rendimento anual extraído comparado com o rendimento anual declarado.',
            recommendation: 'Rever rendimentos declarados e comprovativos quando o valor documental for superior ao declarado.',
        );
    }

    private function incomeMonthly(string $key, string $label, string $extractedPath, DocumentAiValidationSeverity $severity = DocumentAiValidationSeverity::Critical): DocumentValidationRule
    {
        return new DocumentValidationRule(
            group: DocumentAiValidationGroup::Income,
            key: $key,
            label: $label,
            candidatePath: 'income.monthly_total',
            extractedPath: $extractedPath,
            method: DocumentAiComparisonMethod::MoneyTolerance,
            valueType: 'money',
            baseSeverity: $severity,
            sensitive: true,
            income: true,
            message: 'Valor mensal extraído comparado com o rendimento mensal declarado.',
            recommendation: 'Rever recibos e declaração de rendimentos do agregado.',
        );
    }

    private function housingAddress(string $key, string $label, string $extractedPath): DocumentValidationRule
    {
        return new DocumentValidationRule(
            group: DocumentAiValidationGroup::Housing,
            key: $key,
            label: $label,
            candidatePath: 'housing.address',
            extractedPath: $extractedPath,
            method: DocumentAiComparisonMethod::AddressSimilarity,
            valueType: 'address',
            baseSeverity: DocumentAiValidationSeverity::Medium,
            sensitive: true,
            message: 'Morada extraída comparada com a situação habitacional declarada.',
            recommendation: 'Confirmar manualmente a morada quando a similaridade for insuficiente.',
        );
    }

    private function housingRent(string $key, string $label, string $extractedPath): DocumentValidationRule
    {
        return new DocumentValidationRule(
            group: DocumentAiValidationGroup::Housing,
            key: $key,
            label: $label,
            candidatePath: 'housing.rent_amount',
            extractedPath: $extractedPath,
            method: DocumentAiComparisonMethod::MoneyTolerance,
            valueType: 'money',
            baseSeverity: DocumentAiValidationSeverity::Medium,
            sensitive: true,
            income: true,
            message: 'Renda extraída comparada com a renda mensal declarada.',
            recommendation: 'Confirmar contrato de arrendamento e renda declarada.',
        );
    }
}
