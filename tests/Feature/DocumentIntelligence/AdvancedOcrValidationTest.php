<?php

namespace Tests\Feature\DocumentIntelligence;

use App\Enums\DocumentAiOcrStatus;
use App\Services\DocumentIntelligence\DocumentOcrExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvancedOcrValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_source_text_fallback_generates_controlled_ocr_result_for_synthetic_file(): void
    {
        config(['document-ai.ocr.fallback_to_source_text' => true]);
        $path = tempnam(sys_get_temp_dir(), 'qa33-ocr-');
        $this->assertIsString($path);
        file_put_contents($path, 'Documento sintetico de teste com texto suficiente para OCR assistivo controlado.');

        $result = app(DocumentOcrExtractor::class)->extractImage($path);

        @unlink($path);

        $this->assertSame(DocumentAiOcrStatus::Completed, $result->status);
        $this->assertTrue($result->available);
        $this->assertStringContainsString('Documento sintetico', (string) $result->text);
    }

    public function test_unavailable_ocr_engine_fails_controlled_without_document_payload(): void
    {
        config([
            'document-ai.ocr.fallback_to_source_text' => false,
            'document-ai.ocr.binary' => 'qa33-missing-tesseract-binary',
        ]);

        $result = app(DocumentOcrExtractor::class)->extractImage('/tmp/qa33-non-existing-image.png');

        $this->assertSame(DocumentAiOcrStatus::Unavailable, $result->status);
        $this->assertFalse($result->available);
        $this->assertNull($result->text);
        $this->assertSame('tesseract_unavailable', $result->failureCode);
    }
}
