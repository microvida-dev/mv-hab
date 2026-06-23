<?php

namespace Tests\Feature\DocumentIntelligence;

use App\Enums\DocumentAiDocumentType;
use App\Services\DocumentIntelligence\DocumentClassificationScorer;
use App\Services\DocumentIntelligence\DocumentKeywordClassifier;
use App\Services\DocumentIntelligence\DocumentLayoutSignalExtractor;
use App\Services\DocumentIntelligence\LocalAiDocumentClassifier;
use Tests\TestCase;

class DocumentClassificationAccuracyTest extends TestCase
{
    public function test_classifier_reaches_minimum_accuracy_on_supported_synthetic_portuguese_fixtures(): void
    {
        config(['document-ai.ollama.enabled' => false]);
        $fixtures = $this->fixtures();
        $correct = 0;

        foreach ($fixtures as $file => $expected) {
            $text = (string) file_get_contents(base_path('tests/Fixtures/document-intelligence/classification/'.$file));
            $keyword = app(DocumentKeywordClassifier::class)->classify($text);
            $layout = app(DocumentLayoutSignalExtractor::class)->extract($text);
            $ai = app(LocalAiDocumentClassifier::class)->classify($text);
            $result = app(DocumentClassificationScorer::class)->score($keyword, $layout, $ai);

            if ($result->documentType === $expected) {
                $correct++;
            }
        }

        $accuracy = $correct / count($fixtures);

        $this->assertGreaterThanOrEqual(0.90, $accuracy);
    }

    /**
     * @return array<string, DocumentAiDocumentType>
     */
    private function fixtures(): array
    {
        return [
            'cartao_cidadao.txt' => DocumentAiDocumentType::CartaoCidadao,
            'titulo_residencia.txt' => DocumentAiDocumentType::TituloResidencia,
            'passaporte.txt' => DocumentAiDocumentType::Passaporte,
            'irs.txt' => DocumentAiDocumentType::Irs,
            'nota_liquidacao.txt' => DocumentAiDocumentType::NotaLiquidacao,
            'recibo_vencimento.txt' => DocumentAiDocumentType::ReciboVencimento,
            'declaracao_seguranca_social.txt' => DocumentAiDocumentType::DeclaracaoSegurancaSocial,
            'declaracao_at.txt' => DocumentAiDocumentType::DeclaracaoAt,
            'iban.txt' => DocumentAiDocumentType::Iban,
            'contrato_arrendamento.txt' => DocumentAiDocumentType::ContratoArrendamento,
            'comprovativo_morada.txt' => DocumentAiDocumentType::ComprovativoMorada,
            'atestado_multiusos.txt' => DocumentAiDocumentType::AtestadoMultiusos,
            'certidao_escolar.txt' => DocumentAiDocumentType::CertidaoEscolar,
        ];
    }
}
