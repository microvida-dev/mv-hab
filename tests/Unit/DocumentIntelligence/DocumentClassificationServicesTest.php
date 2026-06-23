<?php

namespace Tests\Unit\DocumentIntelligence;

use App\Enums\DocumentAiClassificationStatus;
use App\Enums\DocumentAiDocumentType;
use App\Services\DocumentIntelligence\DocumentClassificationPromptBuilder;
use App\Services\DocumentIntelligence\DocumentClassificationResultNormalizer;
use App\Services\DocumentIntelligence\DocumentClassificationScorer;
use App\Services\DocumentIntelligence\DocumentKeywordClassifier;
use App\Services\DocumentIntelligence\DocumentLayoutSignalExtractor;
use App\Services\DocumentIntelligence\LocalAiDocumentClassifier;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DocumentClassificationServicesTest extends TestCase
{
    public function test_keyword_classifier_identifies_portuguese_document_type(): void
    {
        $result = app(DocumentKeywordClassifier::class)->classify('Nota de liquidacao de IRS Demonstracao de liquidacao Autoridade Tributaria');

        $this->assertSame(DocumentAiDocumentType::NotaLiquidacao, $result->documentType);
        $this->assertGreaterThan(0.80, $result->confidence);
        $this->assertNotEmpty($result->signals);
    }

    public function test_layout_signals_detect_iban_and_contract_patterns(): void
    {
        $iban = app(DocumentLayoutSignalExtractor::class)->extract('IBAN PT50000000000000000000000');
        $contract = app(DocumentLayoutSignalExtractor::class)->extract('Contrato com clausula primeira e renda mensal');

        $this->assertSame(DocumentAiDocumentType::Iban, $iban->documentType);
        $this->assertSame(DocumentAiDocumentType::ContratoArrendamento, $contract->documentType);
    }

    public function test_prompt_builder_uses_strict_json_instruction_and_supported_categories(): void
    {
        $prompt = app(DocumentClassificationPromptBuilder::class)->build('Texto OCR de teste');

        $this->assertStringContainsString('devolva apenas JSON', $prompt);
        $this->assertStringContainsString('cartao_cidadao', $prompt);
        $this->assertStringContainsString('Texto OCR de teste', $prompt);
    }

    public function test_ai_result_normalizer_accepts_json_and_rejects_invalid_payloads(): void
    {
        $normalizer = app(DocumentClassificationResultNormalizer::class);
        $valid = $normalizer->fromJson('{"document_type":"irs","label":"IRS","confidence":0.94,"reason":"modelo 3"}');
        $invalid = $normalizer->fromJson('texto sem json');

        $this->assertSame(DocumentAiDocumentType::Irs, $valid->documentType);
        $this->assertSame(0.94, $valid->confidence);
        $this->assertSame('invalid_ai_json', $invalid->failureCode);
    }

    public function test_scorer_combines_keyword_layout_and_ai_without_requiring_ai(): void
    {
        config(['document-ai.ollama.enabled' => false]);
        $text = 'Contrato de arrendamento Senhorio Arrendatario Renda mensal Clausula primeira';

        $keyword = app(DocumentKeywordClassifier::class)->classify($text);
        $layout = app(DocumentLayoutSignalExtractor::class)->extract($text);
        $ai = app(LocalAiDocumentClassifier::class)->classify($text);
        $result = app(DocumentClassificationScorer::class)->score($keyword, $layout, $ai);

        $this->assertSame(DocumentAiDocumentType::ContratoArrendamento, $result->documentType);
        $this->assertContains($result->status, [
            DocumentAiClassificationStatus::Completed,
            DocumentAiClassificationStatus::ManualReview,
        ]);
        $this->assertStringContainsString('keywords', $result->source);
    }

    public function test_local_ai_classifier_uses_ollama_json_when_enabled(): void
    {
        config(['document-ai.ollama.enabled' => true]);
        Http::fake([
            '127.0.0.1:11434/*' => Http::response([
                'response' => '{"document_type":"iban","label":"IBAN","confidence":0.96,"reason":"padrao iban"}',
            ]),
        ]);

        $result = app(LocalAiDocumentClassifier::class)->classify('Comprovativo de IBAN PT50000000000000000000000');

        $this->assertSame(DocumentAiDocumentType::Iban, $result->documentType);
        $this->assertSame(0.96, $result->confidence);
    }
}
