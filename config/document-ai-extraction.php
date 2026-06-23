<?php

use App\Enums\DocumentAiDocumentType;
use App\Enums\DocumentAiExtractedFieldType;

return [
    'enabled' => env('DOCUMENT_AI_EXTRACTION_ENABLED', true),
    'schema_version' => '1.0',
    'prompt_version' => 'sprint29.field_extraction.v1',

    'thresholds' => [
        'field_review' => (float) env('DOCUMENT_AI_EXTRACTION_FIELD_REVIEW_THRESHOLD', 0.75),
        'document_review' => (float) env('DOCUMENT_AI_EXTRACTION_DOCUMENT_REVIEW_THRESHOLD', 0.80),
    ],

    'ollama' => [
        'enabled' => env('DOCUMENT_AI_EXTRACTION_OLLAMA_ENABLED', false),
        'model' => env('DOCUMENT_AI_OLLAMA_MODEL', 'gemma3:4b'),
        'timeout' => (int) env('DOCUMENT_AI_EXTRACTION_TIMEOUT', 120),
        'max_chars' => (int) env('DOCUMENT_AI_EXTRACTION_MAX_CHARS', 12000),
    ],

    'schemas' => [
        DocumentAiDocumentType::CartaoCidadao->value => [
            'label' => DocumentAiDocumentType::CartaoCidadao->label(),
            'fields' => [
                'name' => ['type' => DocumentAiExtractedFieldType::String, 'label' => 'Nome', 'sensitive' => true, 'health_data' => false, 'required' => true],
                'birth_date' => ['type' => DocumentAiExtractedFieldType::Date, 'label' => 'Data nascimento', 'sensitive' => true, 'health_data' => false, 'required' => true],
                'sex' => ['type' => DocumentAiExtractedFieldType::Enum, 'label' => 'Sexo', 'sensitive' => true, 'health_data' => false, 'required' => false],
                'nationality' => ['type' => DocumentAiExtractedFieldType::String, 'label' => 'Nacionalidade', 'sensitive' => true, 'health_data' => false, 'required' => false],
                'document_number' => ['type' => DocumentAiExtractedFieldType::Identifier, 'label' => 'Número documento', 'sensitive' => true, 'health_data' => false, 'required' => true],
                'expiry_date' => ['type' => DocumentAiExtractedFieldType::Date, 'label' => 'Validade', 'sensitive' => true, 'health_data' => false, 'required' => true],
                'nif' => ['type' => DocumentAiExtractedFieldType::Identifier, 'label' => 'NIF', 'sensitive' => true, 'health_data' => false, 'required' => false],
            ],
        ],
        DocumentAiDocumentType::TituloResidencia->value => [
            'label' => DocumentAiDocumentType::TituloResidencia->label(),
            'fields' => [
                'name' => ['type' => DocumentAiExtractedFieldType::String, 'label' => 'Nome', 'sensitive' => true, 'health_data' => false, 'required' => true],
                'document_number' => ['type' => DocumentAiExtractedFieldType::Identifier, 'label' => 'Número', 'sensitive' => true, 'health_data' => false, 'required' => true],
                'expiry_date' => ['type' => DocumentAiExtractedFieldType::Date, 'label' => 'Validade', 'sensitive' => true, 'health_data' => false, 'required' => true],
                'nationality' => ['type' => DocumentAiExtractedFieldType::String, 'label' => 'Nacionalidade', 'sensitive' => true, 'health_data' => false, 'required' => false],
            ],
        ],
        DocumentAiDocumentType::Irs->value => [
            'label' => DocumentAiDocumentType::Irs->label(),
            'fields' => [
                'fiscal_year' => ['type' => DocumentAiExtractedFieldType::Integer, 'label' => 'Ano fiscal', 'sensitive' => false, 'health_data' => false, 'required' => true],
                'taxpayer_name' => ['type' => DocumentAiExtractedFieldType::String, 'label' => 'Sujeito passivo', 'sensitive' => true, 'health_data' => false, 'required' => true],
                'nif' => ['type' => DocumentAiExtractedFieldType::Identifier, 'label' => 'NIF', 'sensitive' => true, 'health_data' => false, 'required' => true],
                'gross_income' => ['type' => DocumentAiExtractedFieldType::Money, 'label' => 'Rendimento global', 'sensitive' => true, 'health_data' => false, 'required' => true],
                'taxable_income' => ['type' => DocumentAiExtractedFieldType::Money, 'label' => 'Rendimento coletável', 'sensitive' => true, 'health_data' => false, 'required' => true],
            ],
        ],
        DocumentAiDocumentType::NotaLiquidacao->value => [
            'label' => DocumentAiDocumentType::NotaLiquidacao->label(),
            'fields' => [
                'year' => ['type' => DocumentAiExtractedFieldType::Integer, 'label' => 'Ano', 'sensitive' => false, 'health_data' => false, 'required' => true],
                'total_income' => ['type' => DocumentAiExtractedFieldType::Money, 'label' => 'Total rendimento', 'sensitive' => true, 'health_data' => false, 'required' => true],
                'status' => ['type' => DocumentAiExtractedFieldType::String, 'label' => 'Estado', 'sensitive' => false, 'health_data' => false, 'required' => false],
            ],
        ],
        DocumentAiDocumentType::ReciboVencimento->value => [
            'label' => DocumentAiDocumentType::ReciboVencimento->label(),
            'fields' => [
                'employer' => ['type' => DocumentAiExtractedFieldType::String, 'label' => 'Entidade patronal', 'sensitive' => false, 'health_data' => false, 'required' => true],
                'worker' => ['type' => DocumentAiExtractedFieldType::String, 'label' => 'Trabalhador', 'sensitive' => true, 'health_data' => false, 'required' => true],
                'base_salary' => ['type' => DocumentAiExtractedFieldType::Money, 'label' => 'Salário base', 'sensitive' => true, 'health_data' => false, 'required' => true],
                'gross_amount' => ['type' => DocumentAiExtractedFieldType::Money, 'label' => 'Ilíquido', 'sensitive' => true, 'health_data' => false, 'required' => true],
                'net_amount' => ['type' => DocumentAiExtractedFieldType::Money, 'label' => 'Líquido', 'sensitive' => true, 'health_data' => false, 'required' => true],
            ],
        ],
        DocumentAiDocumentType::DeclaracaoSegurancaSocial->value => [
            'label' => DocumentAiDocumentType::DeclaracaoSegurancaSocial->label(),
            'fields' => [
                'beneficiary' => ['type' => DocumentAiExtractedFieldType::String, 'label' => 'Beneficiário', 'sensitive' => true, 'health_data' => false, 'required' => true],
                'beneficiary_number' => ['type' => DocumentAiExtractedFieldType::Identifier, 'label' => 'Número', 'sensitive' => true, 'health_data' => false, 'required' => true],
                'benefit' => ['type' => DocumentAiExtractedFieldType::String, 'label' => 'Prestação', 'sensitive' => true, 'health_data' => false, 'required' => false],
                'amount' => ['type' => DocumentAiExtractedFieldType::Money, 'label' => 'Valor', 'sensitive' => true, 'health_data' => false, 'required' => true],
            ],
        ],
        DocumentAiDocumentType::ContratoArrendamento->value => [
            'label' => DocumentAiDocumentType::ContratoArrendamento->label(),
            'fields' => [
                'landlord' => ['type' => DocumentAiExtractedFieldType::String, 'label' => 'Senhorio', 'sensitive' => true, 'health_data' => false, 'required' => true],
                'tenant' => ['type' => DocumentAiExtractedFieldType::String, 'label' => 'Inquilino', 'sensitive' => true, 'health_data' => false, 'required' => true],
                'address' => ['type' => DocumentAiExtractedFieldType::Address, 'label' => 'Morada', 'sensitive' => true, 'health_data' => false, 'required' => true],
                'rent_amount' => ['type' => DocumentAiExtractedFieldType::Money, 'label' => 'Renda', 'sensitive' => true, 'health_data' => false, 'required' => true],
                'start_date' => ['type' => DocumentAiExtractedFieldType::Date, 'label' => 'Data início', 'sensitive' => false, 'health_data' => false, 'required' => true],
                'end_date' => ['type' => DocumentAiExtractedFieldType::Date, 'label' => 'Data fim', 'sensitive' => false, 'health_data' => false, 'required' => false],
            ],
        ],
        DocumentAiDocumentType::AtestadoMultiusos->value => [
            'label' => DocumentAiDocumentType::AtestadoMultiusos->label(),
            'fields' => [
                'disability_degree' => ['type' => DocumentAiExtractedFieldType::Percentage, 'label' => 'Grau incapacidade', 'sensitive' => true, 'health_data' => true, 'required' => true],
                'issued_at' => ['type' => DocumentAiExtractedFieldType::Date, 'label' => 'Data emissão', 'sensitive' => true, 'health_data' => true, 'required' => true],
                'issuing_entity' => ['type' => DocumentAiExtractedFieldType::String, 'label' => 'Entidade', 'sensitive' => true, 'health_data' => true, 'required' => false],
                'result' => ['type' => DocumentAiExtractedFieldType::String, 'label' => 'Resultado', 'sensitive' => true, 'health_data' => true, 'required' => false],
            ],
        ],
    ],
];
