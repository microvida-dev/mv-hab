<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\DocumentExtractionFlag;
use App\Data\DocumentIntelligence\DocumentExtractionSchema;
use App\Data\DocumentIntelligence\ExtractedDocumentField;
use App\Enums\DocumentAiExtractionSource;

class RegexFieldExtractor
{
    public function __construct(private readonly DocumentFieldNormalizer $normalizer) {}

    /**
     * @return array{fields: list<ExtractedDocumentField>, flags: list<DocumentExtractionFlag>}
     */
    public function extract(string $ocrText, DocumentExtractionSchema $schema): array
    {
        $fields = [];
        $flags = [];
        $labels = $this->labelsForSchema($schema);
        $allLabels = array_values(array_unique(array_merge(...array_values($labels))));

        foreach ($schema->fields as $key => $definition) {
            $raw = $this->extractByLabels($ocrText, $labels[$key] ?? [$definition['label']], $allLabels);
            $normalization = $this->normalizer->normalize($key, $definition['type'], $raw);
            $required = (bool) $definition['required'];
            $missingRequired = $raw === null && $required;
            $requiresReview = (bool) $normalization['requires_review'] || $missingRequired;
            $confidence = $raw === null ? 0.0 : ($requiresReview ? 0.60 : 0.92);

            if ($missingRequired) {
                $flags[] = new DocumentExtractionFlag('missing_required_field', 'medium', 'Campo obrigatório não extraído.', $key);
            }

            foreach ($normalization['flags'] as $flag) {
                $flags[] = $flag;
            }

            $fields[] = new ExtractedDocumentField(
                key: $key,
                label: $definition['label'],
                type: $definition['type'],
                value: $normalization['value'],
                normalizedValue: $normalization['normalized_value'],
                confidence: $confidence,
                source: DocumentAiExtractionSource::Regex,
                requiresReview: $requiresReview,
                sensitive: (bool) $definition['sensitive'],
                healthData: (bool) $definition['health_data'],
            );
        }

        return ['fields' => $fields, 'flags' => $flags];
    }

    /**
     * @return array<string, list<string>>
     */
    private function labelsForSchema(DocumentExtractionSchema $schema): array
    {
        $base = [
            'name' => ['Nome', 'Nome completo'],
            'birth_date' => ['Data nascimento', 'Data de nascimento'],
            'sex' => ['Sexo'],
            'nationality' => ['Nacionalidade'],
            'document_number' => ['Número documento', 'Numero documento', 'Número', 'Numero', 'Nº documento', 'N. documento'],
            'expiry_date' => ['Validade', 'Data validade', 'Data de validade'],
            'nif' => ['NIF', 'Identificação fiscal', 'Identificacao fiscal'],
            'fiscal_year' => ['Ano fiscal'],
            'taxpayer_name' => ['Sujeito passivo'],
            'gross_income' => ['Rendimento global'],
            'taxable_income' => ['Rendimento coletável', 'Rendimento coletavel'],
            'year' => ['Ano'],
            'total_income' => ['Total rendimento', 'Rendimento total'],
            'status' => ['Estado'],
            'employer' => ['Entidade patronal'],
            'worker' => ['Trabalhador'],
            'base_salary' => ['Salário base', 'Salario base'],
            'gross_amount' => ['Ilíquido', 'Iliquido', 'Remuneração ilíquida', 'Remuneracao iliquida'],
            'net_amount' => ['Líquido', 'Liquido', 'Líquido a receber', 'Liquido a receber'],
            'beneficiary' => ['Beneficiário', 'Beneficiario'],
            'beneficiary_number' => ['Número beneficiário', 'Numero beneficiario', 'Número', 'Numero'],
            'benefit' => ['Prestação', 'Prestacao'],
            'amount' => ['Valor'],
            'landlord' => ['Senhorio'],
            'tenant' => ['Inquilino', 'Arrendatário', 'Arrendatario'],
            'address' => ['Morada'],
            'rent_amount' => ['Renda', 'Renda mensal'],
            'start_date' => ['Data início', 'Data inicio'],
            'end_date' => ['Data fim'],
            'disability_degree' => ['Grau incapacidade', 'Grau de incapacidade', 'Incapacidade'],
            'issued_at' => ['Data emissão', 'Data emissao'],
            'issuing_entity' => ['Entidade emissora', 'Entidade'],
            'result' => ['Resultado'],
        ];

        return array_intersect_key($base, $schema->fields);
    }

    /**
     * @param  list<string>  $labels
     * @param  list<string>  $allLabels
     */
    private function extractByLabels(string $text, array $labels, array $allLabels): ?string
    {
        $escapedLabels = implode('|', array_map(static fn (string $label): string => preg_quote($label, '/'), $labels));
        $escapedAllLabels = implode('|', array_map(static fn (string $label): string => preg_quote($label, '/'), $allLabels));
        $pattern = '/(?:^|\s)(?:'.$escapedLabels.')\s*[:\-]\s*(.*?)(?=\s+(?:'.$escapedAllLabels.')\s*[:\-]|$)/iu';

        if (preg_match($pattern, $text, $matches) !== 1) {
            return null;
        }

        $value = trim((string) ($matches[1] ?? ''));

        return $value !== '' ? $value : null;
    }
}
