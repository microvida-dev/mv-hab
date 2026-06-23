<?php

namespace Tests\Unit\DocumentIntelligence;

use App\Data\DocumentIntelligence\ExtractedDocumentField;
use App\Enums\DocumentAiDocumentType;
use App\Enums\DocumentAiExtractedFieldType;
use App\Enums\DocumentAiExtractionStatus;
use App\Services\DocumentIntelligence\DocumentExtractionResultValidator;
use App\Services\DocumentIntelligence\DocumentExtractionSchemaRegistry;
use App\Services\DocumentIntelligence\DocumentExtractionScorer;
use App\Services\DocumentIntelligence\DocumentFieldNormalizer;
use App\Services\DocumentIntelligence\RegexFieldExtractor;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DocumentFieldExtractionServicesTest extends TestCase
{
    /**
     * @return array<string, array{0: DocumentAiDocumentType, 1: string, 2: string}>
     */
    public static function supportedFixtures(): array
    {
        return [
            'cartao_cidadao' => [DocumentAiDocumentType::CartaoCidadao, 'cartao_cidadao.txt', 'nif'],
            'titulo_residencia' => [DocumentAiDocumentType::TituloResidencia, 'titulo_residencia.txt', 'document_number'],
            'irs' => [DocumentAiDocumentType::Irs, 'irs.txt', 'gross_income'],
            'nota_liquidacao' => [DocumentAiDocumentType::NotaLiquidacao, 'nota_liquidacao.txt', 'total_income'],
            'recibo_vencimento' => [DocumentAiDocumentType::ReciboVencimento, 'recibo_vencimento.txt', 'net_amount'],
            'declaracao_seguranca_social' => [DocumentAiDocumentType::DeclaracaoSegurancaSocial, 'declaracao_seguranca_social.txt', 'amount'],
            'contrato_arrendamento' => [DocumentAiDocumentType::ContratoArrendamento, 'contrato_arrendamento.txt', 'rent_amount'],
            'atestado_multiusos' => [DocumentAiDocumentType::AtestadoMultiusos, 'atestado_multiusos.txt', 'disability_degree'],
        ];
    }

    public function test_registry_exposes_required_sprint_29_schemas(): void
    {
        $schemas = app(DocumentExtractionSchemaRegistry::class)->supportedSchemas();

        foreach (array_keys(self::supportedFixtures()) as $documentType) {
            $this->assertArrayHasKey($documentType, $schemas);
            $this->assertNotSame([], $schemas[$documentType]->fields);
        }
    }

    #[DataProvider('supportedFixtures')]
    public function test_regex_extractor_reads_structured_fixture_fields(DocumentAiDocumentType $documentType, string $fixture, string $expectedKey): void
    {
        $schema = app(DocumentExtractionSchemaRegistry::class)->schemaFor($documentType);
        $this->assertNotNull($schema);
        $text = (string) file_get_contents(base_path('tests/Fixtures/document-intelligence/extraction/'.$fixture));
        $result = app(RegexFieldExtractor::class)->extract($text, $schema);
        $fields = collect($result['fields'])->keyBy(fn (ExtractedDocumentField $field): string => $field->key);

        $this->assertTrue($fields->has($expectedKey));
        $this->assertNotNull($fields->get($expectedKey)?->value);
        $this->assertGreaterThanOrEqual(0.90, $fields->get($expectedKey)?->confidence ?? 0.0);
        $this->assertSame([], $result['flags']);
    }

    public function test_normalizer_handles_portuguese_dates_money_percentages_and_identifiers(): void
    {
        $normalizer = app(DocumentFieldNormalizer::class);

        $date = $normalizer->normalize('birth_date', DocumentAiExtractedFieldType::Date, '12/05/1988');
        $money = $normalizer->normalize('gross_income', DocumentAiExtractedFieldType::Money, '18.500,75 EUR');
        $percentage = $normalizer->normalize('disability_degree', DocumentAiExtractedFieldType::Percentage, '60%');
        $nif = $normalizer->normalize('nif', DocumentAiExtractedFieldType::Identifier, '123 456 789');

        $this->assertSame('1988-05-12', $date['normalized_value']);
        $this->assertSame(18500.75, $money['normalized_value']);
        $this->assertSame(60.0, $percentage['normalized_value']);
        $this->assertSame('123456789', $nif['normalized_value']);
    }

    public function test_validator_and_scorer_mark_missing_required_fields_for_review(): void
    {
        $schema = app(DocumentExtractionSchemaRegistry::class)->schemaFor(DocumentAiDocumentType::Irs);
        $this->assertNotNull($schema);

        $validated = app(DocumentExtractionResultValidator::class)->validate($schema, [], []);
        $result = app(DocumentExtractionScorer::class)->score($schema, $validated['fields'], $validated['flags'], 'unit_test');

        $this->assertSame(DocumentAiExtractionStatus::Failed, $result->status);
        $this->assertTrue($result->requiresManualReview);
        $this->assertContains('missing_required_field', array_map(static fn ($flag): string => $flag->code, $result->flags));
    }
}
