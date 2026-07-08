<?php

namespace App\Services\DocumentIntelligence;

use App\Data\DocumentIntelligence\DocumentExtractionFlag;
use App\Data\DocumentIntelligence\DocumentExtractionSchema;
use App\Data\DocumentIntelligence\ExtractedDocumentField;
use App\Enums\DocumentAiExtractionSource;
use App\Enums\DocumentAiDocumentType;

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
            $raw = $this->extractSpecialized($ocrText, $schema, (string) $key)
                ?? $this->extractByLabels($ocrText, $labels[$key] ?? [$definition['label']], $allLabels);
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

    private function extractSpecialized(string $text, DocumentExtractionSchema $schema, string $key): ?string
    {
        return match ($schema->documentType) {
            DocumentAiDocumentType::Irs => $this->extractIrsField($text, $key),
            DocumentAiDocumentType::CartaoCidadao => $this->extractCitizenCardField($text, $key),
            DocumentAiDocumentType::TituloResidencia => $this->extractResidenceCardField($text, $key),
            default => null,
        };
    }

    private function extractIrsField(string $text, string $key): ?string
    {
        $text = $this->normalizeWhitespace($text);

        return match ($key) {
            'fiscal_year' => $this->firstMatch($text, [
                '/\bAno\b.*?\b(?<value>20\d{2})\b/iu',
                '/Comprovativo\s+Mod\.?3\s+IRS\s*:\s*\d{9}(?:\s*,\s*\d{9})?\s*\/\s*(?<value>20\d{2})\s*\//iu',
            ]),

            'nif' => $this->firstMatch($text, [
                '/N\.?\s*º\s+de\s+Contribuinte\s*:\s*(?<value>\d{9})\b/iu',
                '/Comprovativo\s+Mod\.?3\s+IRS\s*:\s*(?<value>\d{9})\b/iu',
                '/Sujeito\s+Passivo\s+A\s+NIF\s+GRAU\s+F\.?A\.?\s+[A-ZÁÀÂÃÉÈÊÍÌÎÓÒÔÕÚÙÛÇ\s]+?\s+\d{2}\s+(?<value>\d{9})\b/iu',
            ]),

            'taxpayer_name' => $this->firstMatch($text, [
                '/Sujeito\s+Passivo\s+A\s+NIF\s+GRAU\s+F\.?A\.?\s+(?<value>[A-ZÁÀÂÃÉÈÊÍÌÎÓÒÔÕÚÙÛÇ\s]+?)\s+\d{2}\s+\d{9}\b/iu',
                '/NOME\s+DO\s+SUJEITO\s+PASSIVO.*?Sujeito\s+Passivo\s+A.*?(?<value>[A-ZÁÀÂÃÉÈÊÍÌÎÓÒÔÕÚÙÛÇ\s]+?)\s+\d{2}\s+\d{9}\b/iu',
            ]),

            default => null,
        };
    }

    /**
     * @param  list<string>  $patterns
     */
    private function firstMatch(string $text, array $patterns): ?string
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches) === 1) {
                $value = trim((string) ($matches['value'] ?? ''));

                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    private function normalizeWhitespace(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    private function extractCitizenCardField(string $text, string $key): ?string
    {
        $text = $this->normalizeWhitespace($text);

        return match ($key) {
            'name' => $this->extractCitizenCardName($text),

            'birth_date' => $this->firstDateMatch($text, [
                '/\bPRT\s+(?<day>\d{2})\s+(?<month>\d{2})\s+(?<year>\d{4})\s+DATA\s+DE\s+VALIDADE/iu',
                '/DATE\s+OF\s+BIRTH.*?\b(?<day>\d{2})\s+(?<month>\d{2})\s+(?<year>\d{4})\b/iu',
            ]),

            'document_number' => $this->firstMatch($text, [
                '/DOCUMENT\s+No.*?No\.?\s*(?<value>\d{8}\s*[A-Z0-9]{4})\b/iu',
                '/\b(?<value>\d{8}\s?[A-Z0-9]{4})\b/iu',
            ]),

            'expiry_date' => $this->firstDateMatch($text, [
                '/\b(?<day>\d{2})(?<month>\d{2})\s*(?<year>20\d{2})\s+EXPIRY\s+DATE\b/iu',
                '/DATA\s+DE\s+VALIDADE.*?\b(?<day>\d{2})\s*(?<month>\d{2})\s*(?<year>20\d{2})\b/iu',
            ]),

            'nif' => $this->firstMatch($text, [
                '/(?:NIDENTIFICACAO\s+FISCAL|NIDENTIFICAÇÃO\s+FISCAL|IDENTIFICACAO\s+FISCAL|IDENTIFICAÇÃO\s+FISCAL|TAX\s+No\.?).*?\b(?<value>\d{9})\b/iu',
            ]),

            'nationality' => $this->firstMatch($text, [
                '/\b(?<value>PRT)\s+\d{2}\s+\d{2}\s+\d{4}\b/iu',
            ]),

            default => null,
        };
    }

    private function extractCitizenCardName(string $text): ?string
    {
        $surname = $this->firstMatch($text, [
            '/APELIDOS.*?SURNAME\s+(?<value>[A-ZÁÀÂÃÉÈÊÍÌÎÓÒÔÕÚÙÛÇ]+(?:\s+[A-ZÁÀÂÃÉÈÊÍÌÎÓÒÔÕÚÙÛÇ]+){0,4})\s+NOME/iu',
        ]);

        $given = $this->firstMatch($text, [
            '/NOME\s+(?:SGVEN\s+)?NAME\s+(?<value>[A-ZÁÀÂÃÉÈÊÍÌÎÓÒÔÕÚÙÛÇ]+(?:\s+[A-ZÁÀÂÃÉÈÊÍÌÎÓÒÔÕÚÙÛÇ]+){0,5})\s+SEXO/iu',
        ]);

        $name = trim(implode(' ', array_filter([$given, $surname])));

        if ($name !== '') {
            return $this->cleanPersonName($name);
        }

        return $this->firstMatch($text, [
            '/([A-ZÁÀÂÃÉÈÊÍÌÎÓÒÔÕÚÙÛÇ]+<+[A-ZÁÀÂÃÉÈÊÍÌÎÓÒÔÕÚÙÛÇ]+<<[A-ZÁÀÂÃÉÈÊÍÌÎÓÒÔÕÚÙÛÇ]+<+[A-ZÁÀÂÃÉÈÊÍÌÎÓÒÔÕÚÙÛÇ]+)/iu',
        ]);
    }

    private function extractResidenceCardField(string $text, string $key): ?string
    {
        $text = $this->normalizeWhitespace($text);

        return match ($key) {
            'name' => $this->extractResidenceCardName($text),

            'document_number' => $this->firstMatch($text, [
                '/CARTAO\s+DE\s+RESID(?:E|Ê)?NCIA\s+(?<value>[A-Z0-9]{6,20})\b/iu',
                '/CARTÃO\s+DE\s+RESID(?:E|Ê)?NCIA\s+(?<value>[A-Z0-9]{6,20})\b/iu',
            ]),

            'expiry_date' => $this->firstDateMatch($text, [
                '/VALIDO\s+ATE\s*(?<day>\d{2})\s*(?<month>\d{2})\s*(?<year>20\d{2})/iu',
                '/VÁLIDO\s+ATÉ\s*(?<day>\d{2})\s*(?<month>\d{2})\s*(?<year>20\d{2})/iu',
            ]),

            'nationality' => $this->firstMatch($text, [
                '/\b(?<value>UE)\b/iu',
            ]),

            'nif' => $this->firstMatch($text, [
                '/(?:N\.?º?\s*IDENTIFICA[CÇ][AÃ]O\s+FISCAL|IDENTIFICA[CÇ][AÃ]O\s+FISCAL|TAX\s+No\.?)\s*(?<value>\d{9})\b/iu',
                '/\b(?<value>[12356789]\d{8})\b/iu',
            ]),

            default => null,
        };
    }

    private function extractResidenceCardName(string $text): ?string
    {
        $value = $this->firstMatch($text, [
            '/\b(?<value>[A-ZÁÀÂÃÉÈÊÍÌÎÓÒÔÕÚÙÛÇ]{3,}(?:\s+[A-ZÁÀÂÃÉÈÊÍÌÎÓÒÔÕÚÙÛÇ]{2,}){2,8})\s+(?:EU\s+){2,}.*?\bVALIDO\b/iu',
            '/NOME.*?(?<value>[A-ZÁÀÂÃÉÈÊÍÌÎÓÒÔÕÚÙÛÇ]{3,}(?:\s+[A-ZÁÀÂÃÉÈÊÍÌÎÓÒÔÕÚÙÛÇ]{2,}){2,8})\s+VALIDO/iu',
        ]);

        return $value !== null ? $this->cleanPersonName($value) : null;
    }

    /**
     * @param  list<string>  $patterns
     */
    private function firstDateMatch(string $text, array $patterns): ?string
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches) === 1) {
                $day = str_pad((string) ($matches['day'] ?? ''), 2, '0', STR_PAD_LEFT);
                $month = str_pad((string) ($matches['month'] ?? ''), 2, '0', STR_PAD_LEFT);
                $year = (string) ($matches['year'] ?? '');

                if (preg_match('/^\d{2}$/', $day) === 1
                    && preg_match('/^\d{2}$/', $month) === 1
                    && preg_match('/^\d{4}$/', $year) === 1) {
                    return "{$day}/{$month}/{$year}";
                }
            }
        }

        return null;
    }

    private function cleanPersonName(string $value): string
    {
        $value = str_replace(['<', '«'], ' ', $value);
        $value = preg_replace('/\b(?:EU|ED|FU|EUE|SIVA|SGVEN|NAME|NOME)\b/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
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
